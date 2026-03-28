<?php
/**
 * ADMIN USER MANAGEMENT ACTIONS
 *
 * Handles POST requests for:
 * - create: Create a new user (and send welcome email)
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
require_once '../includes/config.php';
require_once '../includes/mailer.php';

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
        $email = trim($input['email'] ?? '');
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

        // Validate email if provided
        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Ungültige E-Mail-Adresse.']);
                exit;
            }

            // Check if email already exists
            $emailCheckStmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $emailCheckStmt->execute([$email]);
            if ($emailCheckStmt->fetch()) {
                http_response_code(409);
                echo json_encode(['ok' => false, 'error' => 'E-Mail-Adresse existiert bereits.']);
                exit;
            }
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
            'INSERT INTO users (username, display_name, email, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $display_name, !empty($email) ? $email : null, $passwordHash, $role]);
        $userId = $db->lastInsertId();

        // Send welcome email if email is provided
        if (!empty($email)) {
            $subject = 'Willkommen bei Wochenplaner!';
            $body = <<<HTML
            <html>
                <body style="font-family: Arial, sans-serif; color: #333;">
                    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                        <h2>Willkommen!</h2>
                        <p>Hallo {$display_name},</p>
                        <p>dein Account wurde erstellt. Hier sind deine Anmeldedaten:</p>
                        <div style="background-color: #f5f5f5; border-left: 4px solid #7c3aed; padding: 15px; margin: 20px 0;">
                            <p><strong>Benutzername:</strong> {$username}</p>
                            <p><strong>Passwort:</strong> {$password}</p>
                        </div>
                        <p>Du kannst dich hier anmelden:</p>
                        <p><a href="{APP_URL}" style="background-color: #7c3aed; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">Zur App gehen</a></p>
                        <p style="margin-top: 30px; font-size: 14px; color: #999;">Ändere bitte dein Passwort, wenn du dich das erste Mal anmeldest.</p>
                        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                        <p style="font-size: 12px; color: #999;">© Wochenplaner</p>
                    </div>
                </body>
            </html>
            HTML;

            sendMail($email, $subject, $body);
        }

        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'user' => [
                'id' => (int)$userId,
                'username' => $username,
                'display_name' => $display_name,
                'email' => !empty($email) ? $email : null,
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
