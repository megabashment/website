<?php
/**
 * AUTHENTICATION ENDPOINT
 *
 * Handles login, logout, and session status checks.
 * All responses are JSON.
 *
 * Endpoints:
 * - POST /api/auth.php with {"action":"login", "username":"...", "password":"..."}
 * - POST /api/auth.php with {"action":"logout"}
 * - GET /api/auth.php?action=status
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/db.php';

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
// Invalid action
// ─────────────────────────────────────────────────────────────────────
http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Ungültige Aktion.']);
