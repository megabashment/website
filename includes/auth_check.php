<?php
/**
 * AUTHENTICATION & SESSION GUARD
 *
 * Include this file at the top of every API endpoint that requires authentication.
 * Redirects to login if no valid session exists.
 * Returns JSON error responses.
 */

// Ensure we output JSON
header('Content-Type: application/json; charset=utf-8');

// Session security configuration
if (ini_get('session.cookie_httponly') == 0) {
    ini_set('session.cookie_httponly', 1);
}
if (ini_get('session.cookie_samesite') !== 'Strict') {
    ini_set('session.cookie_samesite', 'Strict');
}
// Enable session.cookie_secure once HTTPS is confirmed
// ini_set('session.cookie_secure', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Nicht authentifiziert.']);
    exit;
}

// Check CSRF token for state-changing requests (POST, PUT, DELETE)
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    $csrfFromHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $csrfFromSession = $_SESSION['csrf_token'] ?? '';

    if (empty($csrfFromSession) || $csrfFromHeader !== $csrfFromSession) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'CSRF-Token ungültig.']);
        exit;
    }
}

// Parse JSON request body if present
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    global $request_body;
    $request_body = json_decode(file_get_contents('php://input'), true);
}
