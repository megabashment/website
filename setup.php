<?php
/**
 * ONE-TIME SETUP SCRIPT - Create initial admin user
 *
 * Usage:
 * 1. Create includes/config.php on Strato with your DB credentials
 * 2. Upload this file to htdocs/
 * 3. Open https://yoursite.com/setup.php in browser
 * 4. Script creates admin user with random password
 * 5. DELETE THIS FILE when done (security)
 */

// Check if setup was already done
if (file_exists(__DIR__ . '/.setup_done')) {
    http_response_code(403);
    echo '<h1>❌ Setup bereits abgeschlossen</h1>';
    echo '<p>Dieses Setup-Script kann nur einmal ausgeführt werden.</p>';
    exit;
}

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';

    $db = getDB();

    // Check if admin user already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE role = ?');
    $stmt->execute(['admin']);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo '<h1>❌ Admin-User existiert bereits</h1>';
        exit;
    }

    // Set password to 'admin' for testing
    $password = 'admin';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert admin user
    $stmt = $db->prepare(
        'INSERT INTO users (username, display_name, password, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute(['admin', 'Administrator', $passwordHash, 'admin']);

    // Mark setup as done
    file_put_contents(__DIR__ . '/.setup_done', 'Setup completed at ' . date('Y-m-d H:i:s'));

    // Display success
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup erfolgreich</title>
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    </head>
    <body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 max-w-md w-full">
            <h1 class="text-3xl font-bold text-green-400 mb-4">✅ Setup erfolgreich!</h1>

            <div class="bg-green-950/30 border border-green-900/50 rounded-lg p-4 mb-6">
                <p class="text-sm text-zinc-300 mb-3"><strong>Admin-User erstellt:</strong></p>
                <div class="bg-zinc-950 rounded p-3 font-mono text-xs">
                    <p class="text-zinc-400">Benutzername: <span class="text-green-400">admin</span></p>
                    <p class="text-zinc-400 mt-2">Passwort: <span class="text-green-400" id="password"><?= htmlspecialchars($password) ?></span></p>
                </div>
                <button onclick="copyPassword()" class="mt-3 w-full px-3 py-2 bg-zinc-800 hover:bg-zinc-700 rounded text-xs text-zinc-300 transition-colors">
                    📋 In Zwischenablage kopieren
                </button>
            </div>

            <p class="text-sm text-zinc-400 mb-4">
                Speichere diese Zugangsdaten und ändere das Passwort nach dem ersten Login.
            </p>

            <p class="text-sm text-yellow-400 bg-yellow-950/30 border border-yellow-900/50 rounded p-3 mb-4">
                ⚠️ <strong>Wichtig:</strong> Bitte <strong>diese Datei (setup.php) von Strato löschen</strong> für die Sicherheit!
            </p>

            <a href="/" class="inline-block w-full text-center px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-sm font-semibold transition-colors">
                → Zur App gehen
            </a>
        </div>

        <script>
            function copyPassword() {
                const pwd = document.getElementById('password').textContent;
                navigator.clipboard.writeText(pwd).then(() => {
                    alert('✅ Passwort kopiert!');
                });
            }
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup-Fehler</title>
        <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    </head>
    <body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 max-w-md w-full">
            <h1 class="text-3xl font-bold text-red-400 mb-4">❌ Setup-Fehler</h1>
            <p class="text-sm text-zinc-300 mb-4">
                <strong>Fehler:</strong>
            </p>
            <div class="bg-red-950/30 border border-red-900/50 rounded p-3 font-mono text-xs text-red-300 mb-4">
                <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <p class="text-sm text-zinc-400">
                Überprüfe, ob:
            </p>
            <ul class="text-sm text-zinc-400 ml-4 mt-2 space-y-1">
                <li>• <code>includes/config.php</code> existiert und Credentials korrekt sind</li>
                <li>• Die Datenbank erreichbar ist</li>
                <li>• Die Tabellen mit <code>schema.sql</code> angelegt wurden</li>
            </ul>
        </div>
    </body>
    </html>
    <?php
}
