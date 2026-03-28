<?php
/**
 * WEEK PLAN API ENDPOINT
 *
 * Handles reading and writing the week plan for the authenticated user.
 * Supports multiple plans via plan_id; falls back to the user's first plan.
 *
 * Endpoints:
 * - GET  /api/weekplan.php?plan_id=X  — fetch a specific plan (own or shared)
 * - GET  /api/weekplan.php            — fetch first own plan (auto-create if none)
 * - POST /api/weekplan.php            — save entire week plan; body: {plan_id, weekPlan}
 */

require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)$_SESSION['user_id'];

/**
 * Returns the plan_id for the current user's first own plan.
 * Creates a default plan if none exists yet.
 */
function getOrCreatePlan(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT id FROM week_plans WHERE owner_id = ? ORDER BY id ASC LIMIT 1');
    $stmt->execute([$userId]);
    $plan = $stmt->fetch();

    if ($plan) {
        return (int)$plan['id'];
    }

    $ins = $db->prepare("INSERT INTO week_plans (owner_id, name) VALUES (?, 'Mein Wochenplan')");
    $ins->execute([$userId]);
    return (int)$db->lastInsertId();
}

/**
 * Checks whether $userId can access $planId.
 * Returns ['allowed' => bool, 'permission' => 'edit'|'view']
 */
function checkPlanAccess(PDO $db, int $planId, int $userId): array {
    // Own plan?
    $own = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
    $own->execute([$planId, $userId]);
    if ($own->fetch()) {
        return ['allowed' => true, 'permission' => 'edit'];
    }

    // Shared plan?
    $shared = $db->prepare(
        'SELECT permission FROM plan_shares WHERE plan_id = ? AND shared_with_user_id = ?'
    );
    $shared->execute([$planId, $userId]);
    $row = $shared->fetch();
    if ($row) {
        return ['allowed' => true, 'permission' => $row['permission']];
    }

    return ['allowed' => false, 'permission' => null];
}

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // GET: Fetch week plan
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $requestedPlanId = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : null;

        if ($requestedPlanId) {
            $access = checkPlanAccess($db, $requestedPlanId, $userId);
            if (!$access['allowed']) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Kein Zugriff auf diesen Plan.']);
                exit;
            }
            $planId     = $requestedPlanId;
            $permission = $access['permission'];
        } else {
            $planId     = getOrCreatePlan($db, $userId);
            $permission = 'edit';
        }

        $stmt = $db->prepare(
            'SELECT day_name, recipe_id FROM week_plan_entries
             WHERE plan_id = ? ORDER BY day_index ASC'
        );
        $stmt->execute([$planId]);
        $entries = $stmt->fetchAll();

        $weekPlan = array_map(function($e) {
            return [
                'day'      => $e['day_name'],
                'recipeId' => $e['recipe_id'],
            ];
        }, $entries);

        http_response_code(200);
        echo json_encode([
            'ok'         => true,
            'plan_id'    => $planId,
            'permission' => $permission,
            'weekPlan'   => $weekPlan,
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST: Save entire week plan (upsert)
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $input    = json_decode(file_get_contents('php://input'), true) ?? [];
        $weekPlan = $input['weekPlan'] ?? [];

        if (!is_array($weekPlan) || count($weekPlan) === 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültiger Wochenplan.']);
            exit;
        }

        // Resolve plan_id
        $requestedPlanId = isset($input['plan_id']) ? (int)$input['plan_id'] : null;
        if ($requestedPlanId) {
            $access = checkPlanAccess($db, $requestedPlanId, $userId);
            if (!$access['allowed']) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Kein Zugriff auf diesen Plan.']);
                exit;
            }
            if ($access['permission'] !== 'edit') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Keine Schreibrechte für diesen Plan.']);
                exit;
            }
            $planId = $requestedPlanId;
        } else {
            $planId = getOrCreatePlan($db, $userId);
        }

        $expectedDays = ['Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'];

        if (count($weekPlan) !== 7) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Wochenplan muss 7 Tage enthalten.']);
            exit;
        }

        $entries = [];
        foreach ($weekPlan as $index => $entry) {
            $day      = trim($entry['day']      ?? '');
            $recipeId = trim($entry['recipeId'] ?? '');

            if (empty($day) || empty($recipeId)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Alle Tage und Rezepte sind erforderlich.']);
                exit;
            }

            if (!in_array($day, $expectedDays)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Ungültiger Tag: ' . htmlspecialchars($day)]);
                exit;
            }

            if (strlen($recipeId) > 64) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Rezept-ID zu lang.']);
                exit;
            }

            $entries[] = [
                'day_name'  => $day,
                'day_index' => $index,
                'recipe_id' => $recipeId,
            ];
        }

        $delStmt = $db->prepare('DELETE FROM week_plan_entries WHERE plan_id = ?');
        $delStmt->execute([$planId]);

        $insStmt = $db->prepare(
            'INSERT INTO week_plan_entries (plan_id, day_name, day_index, recipe_id)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($entries as $entry) {
            $insStmt->execute([
                $planId,
                $entry['day_name'],
                $entry['day_index'],
                $entry['recipe_id'],
            ]);
        }

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Invalid method
    // ─────────────────────────────────────────────────────────────────────
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Verarbeiten der Anfrage.']);
    error_log('Week plan API error: ' . $e->getMessage());
}
