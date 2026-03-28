<?php
/**
 * ADMIN USER MANAGEMENT ACTIONS
 *
 * Handles POST requests for:
 * - create: Create a new user
 * - delete: Delete a user
 * - reset_password: Reset a user's password
 */

header('Content-Type: application/json; charset=utf-8');

// Start session and check admin access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Zugriff verweigert.']);
    exit;
}

require_once '../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? null;

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // ACTION: create
    // ─────────────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $username = trim($input['username'] ?? '');
        $display_name = trim($input['display_name'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'user';

        // Validation
        if (empty($username) || !preg_match('/^[a-z0-9_\-]{2,64}$/i', $username)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültiger Benutzername (2-64 Zeichen, nur alphanumerisch).']);
            exit;
        }

        if (empty($display_name) || strlen($display_name) > 128) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Anzeigename ist erforderlich (max. 128 Zeichen).']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Passwort muss mindestens 8 Zeichen lang sein.']);
            exit;
        }

        if (!in_array($role, ['admin', 'user'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültige Rolle.']);
            exit;
        }

        // Check if username already exists
        $checkStmt = $db->prepare('SELECT id FROM users WHERE username = ?');
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'Benutzername existiert bereits.']);
            exit;
        }

        // Hash password and insert
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare(
            'INSERT INTO users (username, display_name, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $display_name, $passwordHash, $role]);
        $userId = $db->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'user' => [
                'id' => (int)$userId,
                'username' => $username,
                'display_name' => $display_name,
                'role' => $role,
            ]
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACTION: delete
    // ─────────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $userId = (int)($input['user_id'] ?? 0);

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültige Benutzer-ID.']);
            exit;
        }

        // Prevent deleting yourself
        if ($userId === $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Sie können sich selbst nicht löschen.']);
            exit;
        }

        // Check if user exists
        $checkStmt = $db->prepare('SELECT id FROM users WHERE id = ?');
        $checkStmt->execute([$userId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Benutzer nicht gefunden.']);
            exit;
        }

        // Delete (cascade delete of week_plan_entries via FK)
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // ACTION: reset_password
    // ─────────────────────────────────────────────────────────────────────
    if ($action === 'reset_password') {
        $userId = (int)($input['user_id'] ?? 0);
        $newPassword = $input['new_password'] ?? '';

        if ($userId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültige Benutzer-ID.']);
            exit;
        }

        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Passwort muss mindestens 8 Zeichen lang sein.']);
            exit;
        }

        // Check if user exists
        $checkStmt = $db->prepare('SELECT id FROM users WHERE id = ?');
        $checkStmt->execute([$userId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Benutzer nicht gefunden.']);
            exit;
        }

        // Update password
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $userId]);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Invalid action
    // ─────────────────────────────────────────────────────────────────────
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Ungültige Aktion.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Verarbeiten der Anfrage.']);
    error_log('Admin users API error: ' . $e->getMessage());
}
