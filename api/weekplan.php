<?php
/**
 * WEEK PLAN API ENDPOINT
 *
 * Handles reading and writing the week plan for the authenticated user.
 * Internally resolves plan_id from week_plans table.
 * Each user auto-gets a default plan on first access.
 *
 * Endpoints:
 * - GET /api/weekplan.php        — fetch user's week plan
 * - POST /api/weekplan.php       — save entire week plan (upsert)
 */

require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

/**
 * Returns the plan_id for the current user.
 * Creates a default plan if none exists yet.
 */
function getOrCreatePlan(PDO $db, int $userId): int {
    $stmt = $db->prepare('SELECT id FROM week_plans WHERE owner_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $plan = $stmt->fetch();

    if ($plan) {
        return (int)$plan['id'];
    }

    $ins = $db->prepare("INSERT INTO week_plans (owner_id, name) VALUES (?, 'Mein Wochenplan')");
    $ins->execute([$userId]);
    return (int)$db->lastInsertId();
}

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // GET: Fetch user's week plan
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $planId = getOrCreatePlan($db, $userId);

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
        echo json_encode(['ok' => true, 'weekPlan' => $weekPlan]);
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

        $planId = getOrCreatePlan($db, $userId);

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
