<?php
/**
 * DEBUG: Check password hash
 * This script tests if password_hash/verify work correctly
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Debug</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen p-4">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-white">🔍 Password Debug</h1>

        <div class="bg-zinc-900 border border-zinc-800 rounded-lg p-6 space-y-4 mb-6">
            <h2 class="text-lg font-semibold text-zinc-200">Admin-User in Datenbank:</h2>
            <?php
            try {
                $db = getDB();
                $stmt = $db->prepare('SELECT id, username, display_name, role, password FROM users WHERE username = ?');
                $stmt->execute(['admin']);
                $user = $stmt->fetch();

                if ($user) {
                    echo '<div class="bg-zinc-800 rounded p-4 font-mono text-xs text-zinc-300">';
                    echo 'ID: ' . htmlspecialchars($user['id']) . '<br>';
                    echo 'Username: ' . htmlspecialchars($user['username']) . '<br>';
                    echo 'Display Name: ' . htmlspecialchars($user['display_name']) . '<br>';
                    echo 'Role: ' . htmlspecialchars($user['role']) . '<br>';
                    echo 'Password Hash (first 30 chars): ' . htmlspecialchars(substr($user['password'], 0, 30)) . '...<br>';
                    echo '</div>';

                    // Test password verification
                    echo '<h2 class="text-lg font-semibold text-zinc-200 mt-6 mb-3">Password Verification Test:</h2>';

                    $testPassword = 'admin';
                    $isValid = password_verify($testPassword, $user['password']);

                    echo '<div class="' . ($isValid ? 'bg-green-950/30 border-green-900/50' : 'bg-red-950/30 border-red-900/50') . ' border rounded p-4">';
                    echo '<strong>' . ($isValid ? '✅ Passwort "admin" ist KORREKT!' : '❌ Passwort "admin" ist FALSCH!') . '</strong><br>';
                    echo '<br>';

                    if (!$isValid) {
                        echo '<p class="text-sm text-zinc-300 mb-4">Das Passwort passt nicht zum Hash in der Datenbank.</p>';

                        // Generate new hash
                        $newHash = password_hash('admin', PASSWORD_BCRYPT, ['cost' => 12]);
                        echo '<p class="text-sm text-zinc-300 mb-3">Führe folgenden SQL-Befehl in phpMyAdmin aus:</p>';
                        echo '<div class="bg-zinc-950 rounded p-3 text-xs overflow-auto">';
                        echo '<code>UPDATE users SET password = \'' . $newHash . '\' WHERE username = \'admin\';</code>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="text-red-400">❌ Admin-User nicht gefunden!</p>';
                }
            } catch (Exception $e) {
                echo '<p class="text-red-400">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>

        <div class="text-sm text-zinc-500 mt-8">
            <p>⚠️ <strong>Vergiss nicht:</strong> Diesen Script nach Gebrauch von Strato löschen!</p>
        </div>
    </div>
</body>
</html>
