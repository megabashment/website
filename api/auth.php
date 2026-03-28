<?php
/**
 * AUTHENTICATION ENDPOINT
 *
 * Handles login, logout, session status checks, password reset, and forgot password.
 * All responses are JSON.
 *
 * Endpoints:
 * - POST /api/auth.php with {"action":"login", "username":"...", "password":"..."}
 * - POST /api/auth.php with {"action":"logout"}
 * - GET /api/auth.php?action=status
 * - POST /api/auth.php with {"action":"forgot_password", "username":"..."}
 * - POST /api/auth.php with {"action":"confirm_reset", "token":"...", "new_password":"..."}
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/mailer.php';

// Session security configuration
if (ini_get('session.cookie_httponly') == 0) {
    ini_set('session.cookie_httponly', 1);
}
if (ini_get('session.cookie_samesite') !== 'Strict') {
    ini_set('session.cookie_samesite', 'Strict');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parse input (JSON body or form data)
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// Get action from GET query, JSON body, or form data
$action = $_GET['action'] ?? $input['action'] ?? $_POST['action'] ?? null;

// ─────────────────────────────────────────────────────────────────────
// ACTION: status
// ─────────────────────────────────────────────────────────────────────
if ($action === 'status') {
    if (!empty($_SESSION['user_id'])) {
        // User is logged in
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'user' => [
                'id' => (int)$_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'display_name' => $_SESSION['display_name'] ?? '',
                'role' => $_SESSION['role'] ?? 'user',
            ]
        ]);
    } else {
        // Not logged in
        http_response_code(200);
        echo json_encode(['ok' => false]);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────
// ACTION: login
// ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Benutzername und Passwort erforderlich.']);
        exit;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT id, username, display_name, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Ungültige Anmeldedaten.']);
            exit;
        }

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        // Store user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['display_name'] = $user['display_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Update last_login timestamp
        $updateStmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $updateStmt->execute([$user['id']]);

        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role'],
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Login fehlgeschlagen.']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────
// ACTION: logout
// ─────────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    session_unset();
    session_destroy();
    http_response_code(200);
    echo json_encode(['ok' => true]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────
// ACTION: forgot_password
// ─────────────────────────────────────────────────────────────────────
if ($action === 'forgot_password') {
    $username = trim($input['username'] ?? '');

    // Always respond ok to prevent username enumeration
    http_response_code(200);

    if (empty($username)) {
        echo json_encode(['ok' => true, 'message' => 'Falls das Konto existiert, erhältst du eine E-Mail.']);
        exit;
    }

    try {
        $db = getDB();

        // Find user by username
        $stmt = $db->prepare('SELECT id, username, email FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || empty($user['email'])) {
            // User not found or no email — still return ok
            echo json_encode(['ok' => true, 'message' => 'Falls das Konto existiert, erhältst du eine E-Mail.']);
            exit;
        }

        // Generate reset token (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        // Store token in database
        $updateStmt = $db->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
        $updateStmt->execute([$token, $expiresAt, $user['id']]);

        // Send reset email
        $resetUrl = APP_URL . '/reset-password.php?token=' . urlencode($token);
        $subject = 'Passwort zurücksetzen — Wochenplaner';
        $body = <<<HTML
        <html>
            <body style="font-family: Arial, sans-serif; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h2>Passwort zurücksetzen</h2>
                    <p>Hallo {$user['username']},</p>
                    <p>Du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt. Klicke auf den Link unten, um ein neues Passwort zu setzen.</p>
                    <p><a href="{$resetUrl}" style="background-color: #7c3aed; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">Passwort zurücksetzen</a></p>
                    <p>Dieser Link ist 1 Stunde lang gültig.</p>
                    <p>Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail.</p>
                    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
                    <p style="font-size: 12px; color: #999;">© Wochenplaner</p>
                </div>
            </body>
        </html>
        HTML;

        sendMail($user['email'], $subject, $body);

        echo json_encode(['ok' => true, 'message' => 'Falls das Konto existiert, erhältst du eine E-Mail.']);

    } catch (Exception $e) {
        error_log('Forgot password error: ' . $e->getMessage());
        echo json_encode(['ok' => true, 'message' => 'Falls das Konto existiert, erhältst du eine E-Mail.']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────
// ACTION: confirm_reset
// ─────────────────────────────────────────────────────────────────────
if ($action === 'confirm_reset') {
    $token = trim($input['token'] ?? '');
    $newPassword = $input['new_password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Token und Passwort erforderlich.']);
        exit;
    }

    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Passwort muss mindestens 8 Zeichen lang sein.']);
        exit;
    }

    try {
        $db = getDB();

        // Find user by token and check expiration
        $stmt = $db->prepare(
            'SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Link abgelaufen oder ungültig.']);
            exit;
        }

        // Hash new password and update
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $updateStmt = $db->prepare(
            'UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?'
        );
        $updateStmt->execute([$passwordHash, $user['id']]);

        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'Passwort erfolgreich zurückgesetzt.']);

    } catch (Exception $e) {
        error_log('Confirm reset error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Fehler beim Zurücksetzen des Passworts.']);
    }
    exit;
}

// ─────────────────────────────────────────────────────────────────────
// Invalid action
// ─────────────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Ungültige Aktion.']);
