<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit;
}

$displayName = htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username'] ?? '');
$role = $_SESSION['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    * { box-sizing: border-box; }
  </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

<div class="max-w-5xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex items-center justify-between mb-12">
    <h1 class="text-2xl font-semibold text-zinc-300">Dashboard</h1>
    <div class="flex gap-2 items-center flex-wrap">
      <span class="px-3 py-1.5 text-xs font-medium bg-violet-900/40 text-violet-300 rounded-lg">
        👤 <?= $displayName ?>
      </span>
      <?php if ($role === 'admin'): ?>
      <a href="/admin/"
        class="px-3 py-1.5 text-xs font-medium bg-purple-900/40 hover:bg-purple-800/40 rounded-lg transition-colors text-purple-300">
        ⚙ Admin
      </a>
      <?php endif; ?>
      <button onclick="logout()"
        class="px-3 py-1.5 text-xs font-medium bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors text-zinc-300">
        ↪ Abmelden
      </button>
    </div>
  </div>

  <!-- Project Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <!-- Wochenplaner -->
    <a href="/index.php"
      class="group block bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-violet-700/50 rounded-xl p-6 transition-all">
      <div class="w-12 h-12 bg-violet-900/40 group-hover:bg-violet-800/40 rounded-lg flex items-center justify-center mb-4 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-violet-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
      </div>
      <h2 class="text-base font-semibold text-zinc-100 mb-1">Wochenplaner</h2>
      <p class="text-sm text-zinc-500">Rezepte planen · Einkaufen · Genießen</p>
    </a>

  </div>

</div>

<script>
async function logout() {
  await fetch('/api/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'logout' })
  });
  window.location.href = '/login.html';
}
</script>

</body>
</html>
