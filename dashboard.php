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
  <style>* { box-sizing: border-box; }</style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

<div class="max-w-5xl mx-auto px-4 py-8">

  <!-- Header -->
  <div class="flex items-center justify-between mb-10">
    <div>
      <h1 class="text-2xl font-semibold text-zinc-100">Showroom</h1>
      <p class="text-sm text-zinc-500 mt-0.5">Willkommen, <?= $displayName ?></p>
    </div>
    <div class="flex gap-2 items-center flex-wrap">
      <?php if ($role === 'admin'): ?>
      <a href="/admin/" class="px-3 py-1.5 text-xs font-medium bg-purple-900/40 hover:bg-purple-800/40 rounded-lg transition-colors text-purple-300">
        ⚙ Admin
      </a>
      <?php endif; ?>
      <button onclick="logout()" class="px-3 py-1.5 text-xs font-medium bg-zinc-800 hover:bg-zinc-700 rounded-lg transition-colors text-zinc-300">
        ↪ Abmelden
      </button>
    </div>
  </div>

  <!-- Section: Apps -->
  <p class="text-xs font-medium text-zinc-500 uppercase tracking-widest mb-4">Apps</p>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">

    <!-- Wochenplaner — aktiv -->
    <a href="/index.php" class="group block bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-violet-700/50 rounded-xl p-6 transition-all">
      <div class="w-10 h-10 bg-violet-900/40 group-hover:bg-violet-800/40 rounded-lg flex items-center justify-center mb-4 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-violet-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-100 mb-1">Wochenplaner</h2>
      <p class="text-xs text-zinc-500 leading-relaxed">Rezepte planen, Einkaufsliste generieren, Woche organisieren. Mit AI-Vorschlägen.</p>
      <span class="inline-block mt-3 text-xs text-violet-400 font-medium">→ Öffnen</span>
    </a>

    <!-- CSV Migrator — aktiv -->
    <a href="/tools/csv-migrator/" class="group block bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 hover:border-emerald-700/50 rounded-xl p-6 transition-all">
      <div class="w-10 h-10 bg-emerald-900/40 group-hover:bg-emerald-800/40 rounded-lg flex items-center justify-center mb-4 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-emerald-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.125 1.125 0 0 1 18 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0 1 18 7.875v1.5m1.125-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 0 1 6 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M6.375 18h11.25" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-100 mb-1">CSV Migrator</h2>
      <p class="text-xs text-zinc-500 leading-relaxed">CSV hochladen, Spalten analysieren, Encoding erkennen, SQL exportieren.</p>
      <span class="inline-block mt-3 text-xs text-emerald-400 font-medium">→ Öffnen</span>
    </a>

  </div>

  <!-- Section: Tools (Coming Soon) -->
  <p class="text-xs font-medium text-zinc-500 uppercase tracking-widest mb-4">Weitere Tools <span class="text-zinc-600 normal-case tracking-normal font-normal">— in Entwicklung</span></p>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

    <!-- AI Chatbot Widget -->
    <div class="block bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-6 opacity-50 cursor-not-allowed">
      <div class="w-10 h-10 bg-blue-900/30 rounded-lg flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-blue-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-400 mb-1">AI Chatbot Widget</h2>
      <p class="text-xs text-zinc-600 leading-relaxed">Einbettbares Chat-Widget mit Claude AI. Per Script-Tag in jede Website integrierbar.</p>
      <span class="inline-block mt-3 text-xs text-zinc-600 font-medium">Coming Soon</span>
    </div>

    <!-- Invoice Generator -->
    <div class="block bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-6 opacity-50 cursor-not-allowed">
      <div class="w-10 h-10 bg-amber-900/30 rounded-lg flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-400 mb-1">Invoice Generator</h2>
      <p class="text-xs text-zinc-600 leading-relaxed">Rechnungen im Browser erstellen, Positionen kalkulieren, PDF exportieren.</p>
      <span class="inline-block mt-3 text-xs text-zinc-600 font-medium">Coming Soon</span>
    </div>

    <!-- n8n Showcase -->
    <div class="block bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-6 opacity-50 cursor-not-allowed">
      <div class="w-10 h-10 bg-orange-900/30 rounded-lg flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-orange-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-400 mb-1">Automation Showcase</h2>
      <p class="text-xs text-zinc-600 leading-relaxed">n8n Workflows live erleben — Formular → AI → E-Mail, vollautomatisch.</p>
      <span class="inline-block mt-3 text-xs text-zinc-600 font-medium">Coming Soon</span>
    </div>

    <!-- WP AI Plugin -->
    <div class="block bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-6 opacity-50 cursor-not-allowed">
      <div class="w-10 h-10 bg-sky-900/30 rounded-lg flex items-center justify-center mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-sky-400">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
        </svg>
      </div>
      <h2 class="text-sm font-semibold text-zinc-400 mb-1">WordPress AI Plugin</h2>
      <p class="text-xs text-zinc-600 leading-relaxed">Claude AI direkt im Gutenberg Editor — Texte generieren, Meta-Beschreibungen, Produkttexte.</p>
      <span class="inline-block mt-3 text-xs text-zinc-600 font-medium">Coming Soon</span>
    </div>

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
