<?php
/**
 * RESET PASSWORD PAGE
 *
 * User lands here from password reset email link.
 * Validates token and shows form to set new password.
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

$token = $_GET['token'] ?? '';
$isValidToken = false;
$errorMessage = '';

// Validate token if provided
if (!empty($token)) {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        $isValidToken = (bool)$user;

        if (!$isValidToken) {
            $errorMessage = 'Der Reset-Link ist abgelaufen oder ungültig.';
        }
    } catch (Exception $e) {
        error_log('Reset token validation error: ' . $e->getMessage());
        $errorMessage = 'Ein Fehler ist aufgetreten.';
    }
}

if (empty($token)) {
    $errorMessage = 'Kein Reset-Token bereitgestellt.';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Passwort zurücksetzen — Wochenplaner</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    input:focus { outline: none; }
  </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 shadow-xl">

    <div class="text-center mb-8">
      <h1 class="text-4xl mb-2">🔓</h1>
      <h2 class="text-2xl font-bold tracking-tight text-white">Passwort zurücksetzen</h2>
    </div>

    <?php if (!$isValidToken): ?>
      <!-- Invalid Token Message -->
      <div class="bg-red-950/30 border border-red-900/50 rounded-lg p-4 text-center">
        <p class="text-red-400 text-sm mb-4">
          <?= htmlspecialchars($errorMessage) ?>
        </p>
        <a href="/login.html" class="inline-block px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-sm font-semibold transition-colors">
          Zur Anmeldung
        </a>
      </div>

    <?php else: ?>
      <!-- Valid Token - Show Reset Form -->
      <form id="reset-password-form" class="space-y-5">
        <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>" />

        <div>
          <label for="new-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">
            Neues Passwort
          </label>
          <input
            id="new-password"
            type="password"
            placeholder="Mindestens 8 Zeichen"
            minlength="8"
            required
            class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors placeholder-zinc-600 text-zinc-100"
          />
        </div>

        <div>
          <label for="confirm-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">
            Passwort wiederholen
          </label>
          <input
            id="confirm-password"
            type="password"
            placeholder="Passwort wiederholen"
            minlength="8"
            required
            class="w-full bg-zinc-800 border border-zinc-700 rounded-xl px-4 py-2.5 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors placeholder-zinc-600 text-zinc-100"
          />
        </div>

        <div id="error-message" class="hidden text-sm text-red-400 bg-red-950/30 border border-red-900/50 rounded-lg p-3">
          <!-- Error text will be inserted here -->
        </div>

        <button
          type="submit"
          class="w-full px-5 py-2.5 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-lg shadow-violet-900/30"
        >
          Passwort speichern
        </button>

        <div class="text-center">
          <a href="/login.html" class="text-sm text-zinc-400 hover:text-zinc-300 transition-colors">
            Zur Anmeldung
          </a>
        </div>

      </form>
    <?php endif; ?>

  </div>
</div>

<script>
document.getElementById('reset-password-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const token = document.getElementById('token').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const errorDiv = document.getElementById('error-message');

  // Clear previous errors
  errorDiv.classList.add('hidden');
  errorDiv.textContent = '';

  // Check if passwords match
  if (newPassword !== confirmPassword) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Passwörter stimmen nicht überein.';
    return;
  }

  try {
    const response = await fetch('/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'confirm_reset',
        token: token,
        new_password: newPassword
      })
    });

    const data = await response.json();

    if (data.ok) {
      alert('✅ Passwort erfolgreich zurückgesetzt! Du wirst nun zur Anmeldung weitergeleitet.');
      window.location.href = '/login.html';
    } else {
      errorDiv.classList.remove('hidden');
      errorDiv.textContent = data.error || 'Fehler beim Zurücksetzen des Passworts.';
    }
  } catch (error) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
    console.error('Reset password error:', error);
  }
});

// Check if already logged in and redirect
(async () => {
  try {
    const response = await fetch('/api/auth.php?action=status');
    const data = await response.json();
    if (data.ok) {
      window.location.href = '/index.php';
    }
  } catch (error) {
    // Silently ignore — not logged in
  }
})();
</script>

</body>
</html>
