<?php
/**
 * WEEK PLAN API ENDPOINT
 *
 * Handles reading and writing the week plan for the authenticated user.
 * Each user has their own independent week plan.
 *
 * Endpoints:
 * - GET /api/weekplan.php        — fetch user's week plan
 * - POST /api/weekplan.php       — save entire week plan (upsert)
 */

// Load auth check (validates session and CSRF token)
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // GET: Fetch user's week plan
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $db->prepare(
            'SELECT day_name, recipe_id FROM week_plan_entries
             WHERE user_id = ? ORDER BY day_index ASC'
        );
        $stmt->execute([$userId]);
        $entries = $stmt->fetchAll();

        // Transform to frontend format
        $weekPlan = array_map(function($e) {
            return [
                'day' => $e['day_name'],
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
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $weekPlan = $input['weekPlan'] ?? [];

        if (!is_array($weekPlan) || count($weekPlan) === 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültiger Wochenplan.']);
            exit;
        }

        // Expected days in order
        $expectedDays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

        if (count($weekPlan) !== 7) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Wochenplan muss 7 Tage enthalten.']);
            exit;
        }

        // Validate and prepare data
        $entries = [];
        foreach ($weekPlan as $index => $entry) {
            $day = trim($entry['day'] ?? '');
            $recipeId = trim($entry['recipeId'] ?? '');

            if (empty($day) || empty($recipeId)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Alle Tage und Rezepte sind erforderlich.']);
                exit;
            }

            // Validate day name
            if (!in_array($day, $expectedDays)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Ungültiger Tag: ' . htmlspecialchars($day)]);
                exit;
            }

            // Validate recipe_id length
            if (strlen($recipeId) > 64) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Rezept-ID zu lang.']);
                exit;
            }

            $entries[] = [
                'day_name' => $day,
                'day_index' => $index,
                'recipe_id' => $recipeId,
            ];
        }

        // Delete existing entries for this user
        $delStmt = $db->prepare('DELETE FROM week_plan_entries WHERE user_id = ?');
        $delStmt->execute([$userId]);

        // Insert all new entries
        $insStmt = $db->prepare(
            'INSERT INTO week_plan_entries (user_id, day_name, day_index, recipe_id)
             VALUES (?, ?, ?, ?)'
        );

        foreach ($entries as $entry) {
            $insStmt->execute([
                $userId,
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
