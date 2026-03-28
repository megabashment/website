<?php
/**
 * PLANS API ENDPOINT
 *
 * Manages week plans (list, create, delete) and plan sharing.
 *
 * Endpoints:
 * - GET  /api/plans.php                    — list own + shared plans
 * - POST /api/plans.php {action:"create"}  — create a new plan (premium only, max 2)
 * - POST /api/plans.php {action:"share"}   — share plan with a user (premium + owner)
 * - POST /api/plans.php {action:"unshare"} — remove a share (owner only)
 * - POST /api/plans.php {action:"shares"}  — list shares for a plan (owner only)
 * - POST /api/plans.php {action:"rename"}  — rename a plan (owner only)
 * - DELETE /api/plans.php {plan_id}        — delete a plan (owner, not last own)
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = (int)$_SESSION['user_id'];
$tier   = $_SESSION['tier'] ?? 'free';

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // GET: list all plans accessible to the user (own + shared)
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $ownStmt = $db->prepare(
            'SELECT id, name FROM week_plans WHERE owner_id = ? ORDER BY id ASC'
        );
        $ownStmt->execute([$userId]);
        $ownPlans = $ownStmt->fetchAll();

        $sharedStmt = $db->prepare(
            'SELECT wp.id, wp.name, ps.permission
             FROM plan_shares ps
             JOIN week_plans wp ON wp.id = ps.plan_id
             WHERE ps.shared_with_user_id = ?
             ORDER BY wp.id ASC'
        );
        $sharedStmt->execute([$userId]);
        $sharedPlans = $sharedStmt->fetchAll();

        $plans = [];
        foreach ($ownPlans as $p) {
            $plans[] = [
                'id'         => (int)$p['id'],
                'name'       => $p['name'],
                'relation'   => 'owner',
                'permission' => 'edit',
            ];
        }
        foreach ($sharedPlans as $p) {
            $plans[] = [
                'id'         => (int)$p['id'],
                'name'       => $p['name'],
                'relation'   => 'shared',
                'permission' => $p['permission'],
            ];
        }

        http_response_code(200);
        echo json_encode(['ok' => true, 'plans' => $plans, 'tier' => $tier]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // POST: various actions
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';

        // ── create ────────────────────────────────────────────────────────
        if ($action === 'create') {
            if ($tier !== 'premium') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Mehrere Pläne sind nur für Premium-Nutzer verfügbar.']);
                exit;
            }

            $countStmt = $db->prepare('SELECT COUNT(*) FROM week_plans WHERE owner_id = ?');
            $countStmt->execute([$userId]);
            if ((int)$countStmt->fetchColumn() >= 2) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Premium-Nutzer können maximal 2 Pläne haben.']);
                exit;
            }

            $name = trim($input['name'] ?? 'Wochenplan 2');
            if (empty($name) || strlen($name) > 128) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Ungültiger Planname.']);
                exit;
            }

            $ins = $db->prepare('INSERT INTO week_plans (owner_id, name) VALUES (?, ?)');
            $ins->execute([$userId, $name]);
            $planId = (int)$db->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'ok'   => true,
                'plan' => ['id' => $planId, 'name' => $name, 'relation' => 'owner', 'permission' => 'edit'],
            ]);
            exit;
        }

        // ── share ─────────────────────────────────────────────────────────
        if ($action === 'share') {
            if ($tier !== 'premium') {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Teilen ist nur für Premium-Nutzer verfügbar.']);
                exit;
            }

            $planId         = (int)($input['plan_id'] ?? 0);
            $targetUsername = trim($input['username'] ?? '');
            $permission     = in_array($input['permission'] ?? 'view', ['view', 'edit'])
                              ? ($input['permission'] ?? 'view') : 'view';

            if ($planId <= 0 || empty($targetUsername)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'plan_id und username erforderlich.']);
                exit;
            }

            // Verify caller owns the plan
            $ownerStmt = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
            $ownerStmt->execute([$planId, $userId]);
            if (!$ownerStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Nur der Plan-Eigentümer kann teilen.']);
                exit;
            }

            // Find target user
            $userStmt = $db->prepare('SELECT id, display_name FROM users WHERE username = ? AND status = "active"');
            $userStmt->execute([$targetUsername]);
            $targetUser = $userStmt->fetch();

            if (!$targetUser) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Benutzer nicht gefunden.']);
                exit;
            }

            if ((int)$targetUser['id'] === $userId) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Du kannst einen Plan nicht mit dir selbst teilen.']);
                exit;
            }

            // Upsert share
            $shareStmt = $db->prepare(
                'INSERT INTO plan_shares (plan_id, shared_with_user_id, permission)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE permission = VALUES(permission)'
            );
            $shareStmt->execute([$planId, (int)$targetUser['id'], $permission]);

            http_response_code(200);
            echo json_encode([
                'ok'          => true,
                'shared_with' => [
                    'id'           => (int)$targetUser['id'],
                    'display_name' => $targetUser['display_name'],
                    'permission'   => $permission,
                ],
            ]);
            exit;
        }

        // ── unshare ───────────────────────────────────────────────────────
        if ($action === 'unshare') {
            $planId       = (int)($input['plan_id'] ?? 0);
            $targetUserId = (int)($input['user_id'] ?? 0);

            if ($planId <= 0 || $targetUserId <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'plan_id und user_id erforderlich.']);
                exit;
            }

            // Verify ownership
            $ownerStmt = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
            $ownerStmt->execute([$planId, $userId]);
            if (!$ownerStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Nur der Plan-Eigentümer kann Freigaben entfernen.']);
                exit;
            }

            $delStmt = $db->prepare('DELETE FROM plan_shares WHERE plan_id = ? AND shared_with_user_id = ?');
            $delStmt->execute([$planId, $targetUserId]);

            http_response_code(200);
            echo json_encode(['ok' => true]);
            exit;
        }

        // ── shares (list shares for a plan) ───────────────────────────────
        if ($action === 'shares') {
            $planId = (int)($input['plan_id'] ?? 0);

            if ($planId <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'plan_id erforderlich.']);
                exit;
            }

            // Verify ownership
            $ownerStmt = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
            $ownerStmt->execute([$planId, $userId]);
            if (!$ownerStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Kein Zugriff.']);
                exit;
            }

            $stmt = $db->prepare(
                'SELECT u.id, u.username, u.display_name, ps.permission
                 FROM plan_shares ps
                 JOIN users u ON u.id = ps.shared_with_user_id
                 WHERE ps.plan_id = ?
                 ORDER BY u.display_name ASC'
            );
            $stmt->execute([$planId]);
            $rows = $stmt->fetchAll();

            $shares = array_map(fn($s) => [
                'id'           => (int)$s['id'],
                'username'     => $s['username'],
                'display_name' => $s['display_name'],
                'permission'   => $s['permission'],
            ], $rows);

            http_response_code(200);
            echo json_encode(['ok' => true, 'shares' => $shares]);
            exit;
        }

        // ── rename ────────────────────────────────────────────────────────
        if ($action === 'rename') {
            $planId = (int)($input['plan_id'] ?? 0);
            $name   = trim($input['name'] ?? '');

            if ($planId <= 0 || empty($name) || strlen($name) > 128) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Ungültige Eingabe.']);
                exit;
            }

            $ownerStmt = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
            $ownerStmt->execute([$planId, $userId]);
            if (!$ownerStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Kein Zugriff.']);
                exit;
            }

            $db->prepare('UPDATE week_plans SET name = ? WHERE id = ?')->execute([$name, $planId]);

            http_response_code(200);
            echo json_encode(['ok' => true]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ungültige Aktion.']);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE: remove a plan (owner only, not last own plan)
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $planId = (int)($input['plan_id'] ?? 0);

        if ($planId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'plan_id erforderlich.']);
            exit;
        }

        $ownerStmt = $db->prepare('SELECT id FROM week_plans WHERE id = ? AND owner_id = ?');
        $ownerStmt->execute([$planId, $userId]);
        if (!$ownerStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Kein Zugriff.']);
            exit;
        }

        $countStmt = $db->prepare('SELECT COUNT(*) FROM week_plans WHERE owner_id = ?');
        $countStmt->execute([$userId]);
        if ((int)$countStmt->fetchColumn() <= 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Du kannst deinen letzten Plan nicht löschen.']);
            exit;
        }

        $db->prepare('DELETE FROM week_plans WHERE id = ? AND owner_id = ?')->execute([$planId, $userId]);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Verarbeiten der Anfrage.']);
    error_log('Plans API error: ' . $e->getMessage());
}
