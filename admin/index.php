<?php
/**
 * ADMIN PANEL - USER MANAGEMENT
 *
 * Lists all users and allows creation/deletion/password reset.
 * Only accessible to admin users.
 */

// Session and admin check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.html');
    exit;
}

require_once '../includes/db.php';

$db = getDB();

// Fetch active users
$stmt = $db->query('SELECT id, username, display_name, email, role, created_at, last_login FROM users WHERE status = "active" ORDER BY created_at DESC');
$users = $stmt->fetchAll();

// Fetch pending users
$pendingStmt = $db->query('SELECT id, username, display_name, email, created_at FROM users WHERE status = "pending" ORDER BY created_at DESC');
$pendingUsers = $pendingStmt->fetchAll();

$currentUserId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wochenplaner — Admin</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    input:focus { outline: none; }
    input[type="password"] { font-family: monospace; }
  </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen p-4">

<div class="max-w-4xl mx-auto">

  <!-- Header -->
  <div class="flex items-center justify-between mb-8 pt-4">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-white">🔑 Admin Panel</h1>
      <p class="text-zinc-500 text-sm mt-1">Benutzerverwaltung</p>
    </div>
    <a href="/" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors text-zinc-300 text-sm">
      ← Zur App
    </a>
  </div>

  <!-- Create User Section -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 mb-8">
    <h2 class="text-lg font-semibold mb-5 text-zinc-200">Neuer Benutzer</h2>
    <form id="create-user-form" class="space-y-4">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="new-username" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">Benutzername</label>
          <input type="text" id="new-username" name="username" placeholder="z. B. papa"
            pattern="[a-z0-9_\-]{2,64}" required
            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors" />
        </div>

        <div>
          <label for="new-display-name" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">Anzeigename</label>
          <input type="text" id="new-display-name" name="display_name" placeholder="z. B. Papa"
            maxlength="128" required
            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors" />
        </div>

        <div>
          <label for="new-email" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">E-Mail (optional)</label>
          <input type="email" id="new-email" name="email" placeholder="z. B. papa@example.de"
            maxlength="255"
            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="new-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">Passwort</label>
          <input type="password" id="new-password" name="password" placeholder="Mind. 8 Zeichen"
            minlength="8" required
            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors" />
        </div>

        <div>
          <label for="new-role" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">Rolle</label>
          <select id="new-role" name="role"
            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors">
            <option value="user">Benutzer</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>

      <div id="create-error" class="hidden text-sm text-red-400 bg-red-950/30 border border-red-900/50 rounded-lg p-3"></div>

      <button type="submit"
        class="px-5 py-2 bg-violet-600 hover:bg-violet-500 active:bg-violet-700 text-white rounded-lg text-sm font-semibold transition-colors shadow-lg shadow-violet-900/30">
        ➕ Benutzer erstellen
      </button>
    </form>
  </div>

  <!-- Pending Users Section -->
  <?php if (!empty($pendingUsers)): ?>
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-zinc-800 bg-amber-950/20">
      <h2 class="text-lg font-semibold text-amber-300">⏳ Ausstehende Genehmigungen (<?= count($pendingUsers) ?>)</h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-zinc-800/50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Benutzername</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Anzeigename</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">E-Mail</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Registriert am</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Aktionen</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800">
          <?php foreach ($pendingUsers as $user): ?>
          <tr class="hover:bg-zinc-800/30 transition-colors">
            <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($user['username']) ?></td>
            <td class="px-6 py-4"><?= htmlspecialchars($user['display_name']) ?></td>
            <td class="px-6 py-4 text-xs text-zinc-400"><?= htmlspecialchars($user['email'] ?? '—') ?></td>
            <td class="px-6 py-4 text-zinc-500 text-xs"><?= htmlspecialchars(substr($user['created_at'], 0, 10)) ?></td>
            <td class="px-6 py-4 space-x-2">
              <button onclick="approveUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"
                class="px-3 py-1 bg-zinc-800 hover:bg-green-900/60 text-green-400 hover:text-green-300 rounded text-xs transition-colors">
                ✓ Freischalten
              </button>
              <button onclick="rejectUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"
                class="px-3 py-1 bg-zinc-800 hover:bg-red-900/60 text-red-400 hover:text-red-300 rounded text-xs transition-colors">
                ✕ Ablehnen
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Users List -->
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-800">
      <h2 class="text-lg font-semibold text-zinc-200"><?= htmlspecialchars(count($users)) ?> Benutzer</h2>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-zinc-800/50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Benutzername</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Anzeigename</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Rolle</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Letzter Login</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-400 uppercase">Aktionen</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800">
          <?php foreach ($users as $user): ?>
          <tr class="hover:bg-zinc-800/30 transition-colors">
            <td class="px-6 py-4 font-mono text-sm"><?= htmlspecialchars($user['username']) ?></td>
            <td class="px-6 py-4"><?= htmlspecialchars($user['display_name']) ?></td>
            <td class="px-6 py-4">
              <span class="px-2 py-1 rounded text-xs font-medium
                <?= $user['role'] === 'admin' ? 'bg-purple-900/40 text-purple-300' : 'bg-zinc-800 text-zinc-400' ?>">
                <?= htmlspecialchars($user['role']) ?>
              </span>
            </td>
            <td class="px-6 py-4 text-zinc-500 text-xs">
              <?= $user['last_login'] ? htmlspecialchars(substr($user['last_login'], 0, 10)) : '—' ?>
            </td>
            <td class="px-6 py-4 space-x-2">
              <button onclick="openResetForm(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"
                class="px-3 py-1 bg-zinc-800 hover:bg-yellow-900/60 text-yellow-400 hover:text-yellow-300 rounded text-xs transition-colors">
                🔑 PW
              </button>
              <?php if ($user['id'] !== $currentUserId): ?>
              <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username'])) ?>')"
                class="px-3 py-1 bg-zinc-800 hover:bg-red-900/60 text-red-400 hover:text-red-300 rounded text-xs transition-colors">
                ✕ Löschen
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Reset Password Modal (inline) -->
<div id="reset-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
  <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-6 max-w-sm w-full">
    <h3 class="text-lg font-semibold mb-4 text-zinc-200">Passwort zurücksetzen</h3>
    <p class="text-sm text-zinc-400 mb-4">Für: <span id="reset-username" class="font-mono text-zinc-300"></span></p>

    <form id="reset-password-form" class="space-y-4">
      <input type="hidden" id="reset-user-id" />
      <div>
        <label for="reset-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-2">Neues Passwort</label>
        <input type="password" id="reset-password" placeholder="Mind. 8 Zeichen"
          minlength="8" required
          class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500/30 transition-colors" />
      </div>

      <div id="reset-error" class="hidden text-sm text-red-400 bg-red-950/30 border border-red-900/50 rounded-lg p-3"></div>

      <div class="flex gap-3">
        <button type="button" onclick="closeResetForm()"
          class="flex-1 px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-sm font-medium transition-colors">
          Abbrechen
        </button>
        <button type="submit"
          class="flex-1 px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-lg text-sm font-semibold transition-colors">
          Speichern
        </button>
      </div>
    </form>
  </div>
</div>

<script>
async function submitCreateForm(e) {
  e.preventDefault();
  const username = document.getElementById('new-username').value.trim();
  const display_name = document.getElementById('new-display-name').value.trim();
  const email = document.getElementById('new-email').value.trim();
  const password = document.getElementById('new-password').value;
  const role = document.getElementById('new-role').value;
  const errorDiv = document.getElementById('create-error');

  errorDiv.classList.add('hidden');
  errorDiv.textContent = '';

  try {
    const res = await fetch('/admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'create', username, display_name, email, password, role })
    });

    const data = await res.json();

    if (data.ok) {
      alert('✅ Benutzer erstellt!');
      location.reload();
    } else {
      errorDiv.classList.remove('hidden');
      errorDiv.textContent = data.error || 'Fehler beim Erstellen.';
    }
  } catch (error) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Fehler beim Senden der Anfrage.';
    console.error(error);
  }
}

function openResetForm(userId, username) {
  document.getElementById('reset-user-id').value = userId;
  document.getElementById('reset-username').textContent = username;
  document.getElementById('reset-password').value = '';
  document.getElementById('reset-error').classList.add('hidden');
  document.getElementById('reset-modal').classList.remove('hidden');
}

function closeResetForm() {
  document.getElementById('reset-modal').classList.add('hidden');
}

async function submitResetForm(e) {
  e.preventDefault();
  const userId = parseInt(document.getElementById('reset-user-id').value);
  const newPassword = document.getElementById('reset-password').value;
  const errorDiv = document.getElementById('reset-error');

  errorDiv.classList.add('hidden');
  errorDiv.textContent = '';

  try {
    const res = await fetch('/admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reset_password', user_id: userId, new_password: newPassword })
    });

    const data = await res.json();

    if (data.ok) {
      alert('✅ Passwort aktualisiert!');
      closeResetForm();
    } else {
      errorDiv.classList.remove('hidden');
      errorDiv.textContent = data.error || 'Fehler beim Aktualisieren.';
    }
  } catch (error) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Fehler beim Senden der Anfrage.';
    console.error(error);
  }
}

async function deleteUser(userId, username) {
  if (!confirm(`Benutzer "${username}" wirklich löschen?\nDie Daten dieses Benutzers (Wochenplan) werden gelöscht.`)) {
    return;
  }

  try {
    const res = await fetch('/admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', user_id: userId })
    });

    const data = await res.json();

    if (data.ok) {
      alert('✅ Benutzer gelöscht!');
      location.reload();
    } else {
      alert(data.error || 'Fehler beim Löschen.');
    }
  } catch (error) {
    alert('Fehler beim Senden der Anfrage.');
    console.error(error);
  }
}

async function approveUser(userId, username) {
  if (!confirm(`Benutzer "${username}" wirklich freischalten?`)) {
    return;
  }

  try {
    const res = await fetch('/admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'approve', user_id: userId })
    });

    const data = await res.json();

    if (data.ok) {
      alert('✅ Benutzer freigegeben!');
      location.reload();
    } else {
      alert(data.error || 'Fehler beim Freischalten.');
    }
  } catch (error) {
    alert('Fehler beim Senden der Anfrage.');
    console.error(error);
  }
}

async function rejectUser(userId, username) {
  if (!confirm(`Benutzer "${username}" wirklich ablehnen und löschen?`)) {
    return;
  }

  try {
    const res = await fetch('/admin/users.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'reject', user_id: userId })
    });

    const data = await res.json();

    if (data.ok) {
      alert('✅ Benutzer abgelehnt!');
      location.reload();
    } else {
      alert(data.error || 'Fehler beim Ablehnen.');
    }
  } catch (error) {
    alert('Fehler beim Senden der Anfrage.');
    console.error(error);
  }
}

// Event listeners
document.getElementById('create-user-form').addEventListener('submit', submitCreateForm);
document.getElementById('reset-password-form').addEventListener('submit', submitResetForm);

// Close modal on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeResetForm();
});
</script>

</body>
</html>
