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
  <title>Passwort zurücksetzen</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; overflow-x: hidden; overflow-y: auto; }
    input:focus { outline: none; }
  </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

<canvas id="bg" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;"></canvas>

<div class="min-h-screen flex items-center justify-center p-4" style="position:relative;z-index:1;">
  <div class="w-full max-w-sm" style="backdrop-filter:blur(2px);">

    <div class="text-center mb-8">
      <h2 class="text-2xl font-semibold text-zinc-300">Passwort zurücksetzen</h2>
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
          <label for="new-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-3">
            Neues Passwort
          </label>
          <input
            id="new-password"
            type="password"
            placeholder=""
            minlength="8"
            required
            class="w-full bg-transparent border-b border-zinc-700 px-0 py-2 text-sm focus:border-violet-500 transition-colors placeholder-zinc-700 text-zinc-100"
          />
        </div>

        <div>
          <label for="confirm-password" class="block text-xs text-zinc-500 uppercase tracking-widest mb-3">
            Passwort wiederholen
          </label>
          <input
            id="confirm-password"
            type="password"
            placeholder=""
            minlength="8"
            required
            class="w-full bg-transparent border-b border-zinc-700 px-0 py-2 text-sm focus:border-violet-500 transition-colors placeholder-zinc-700 text-zinc-100"
          />
        </div>

        <div id="error-message" class="hidden text-sm text-red-400 bg-red-950/30 border border-red-900/50 rounded-lg p-3">
          <!-- Error text will be inserted here -->
        </div>

        <div id="success-message" class="hidden text-sm text-green-400 bg-green-950/30 border border-green-900/50 rounded-lg p-3">
          <!-- Success text will be inserted here -->
        </div>

        <button
          type="submit"
          class="w-full mt-8 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-sm font-semibold transition-colors"
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
const EYE_OPEN = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>`;
const EYE_CLOSED = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>`;

function setupPasswordToggle(inputId) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const wrapper = document.createElement('div');
  wrapper.style.position = 'relative';
  input.parentNode.insertBefore(wrapper, input);
  wrapper.appendChild(input);
  input.style.paddingRight = '1.75rem';
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.tabIndex = -1;
  btn.setAttribute('aria-label', 'Passwort anzeigen');
  btn.style.cssText = 'position:absolute;right:0;top:50%;transform:translateY(-50%);display:none;background:none;border:none;cursor:pointer;color:#71717a;padding:2px;';
  btn.innerHTML = EYE_OPEN;
  wrapper.appendChild(btn);
  input.addEventListener('input', () => {
    btn.style.display = input.value.length >= 8 ? 'flex' : 'none';
  });
  btn.addEventListener('mouseenter', () => btn.style.color = '#d4d4d8');
  btn.addEventListener('mouseleave', () => btn.style.color = '#71717a');
  btn.addEventListener('click', () => {
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    btn.innerHTML = visible ? EYE_OPEN : EYE_CLOSED;
    input.focus();
  });
}

setupPasswordToggle('new-password');
setupPasswordToggle('confirm-password');

// Realtime password match check
const pwField = document.getElementById('new-password');
const pwConfirmField = document.getElementById('confirm-password');
if (pwField && pwConfirmField) {
  function checkPasswordMatch() {
    if (pwConfirmField.value.length === 0) {
      pwConfirmField.style.borderColor = '';
      return;
    }
    pwConfirmField.style.borderColor = pwField.value === pwConfirmField.value ? '#22c55e' : '#ef4444';
  }
  pwField.addEventListener('input', checkPasswordMatch);
  pwConfirmField.addEventListener('input', checkPasswordMatch);
}

document.getElementById('reset-password-form')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const token = document.getElementById('token').value;
  const newPassword = document.getElementById('new-password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const errorDiv = document.getElementById('error-message');
  const successDiv = document.getElementById('success-message');
  const submitBtn = e.target.querySelector('button[type="submit"]');

  errorDiv.classList.add('hidden');
  successDiv.classList.add('hidden');
  errorDiv.textContent = '';
  successDiv.textContent = '';

  if (newPassword !== confirmPassword) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Passwörter stimmen nicht überein.';
    return;
  }

  submitBtn.disabled = true;
  submitBtn.textContent = 'Wird gespeichert…';

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
      successDiv.classList.remove('hidden');
      successDiv.textContent = 'Passwort erfolgreich zurückgesetzt! Du wirst zur Anmeldung weitergeleitet…';
      submitBtn.textContent = 'Gespeichert!';
      setTimeout(() => {
        window.location.href = '/login.html';
      }, 2000);
    } else {
      errorDiv.classList.remove('hidden');
      errorDiv.textContent = data.error || 'Fehler beim Zurücksetzen des Passworts.';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Passwort speichern';
    }
  } catch (error) {
    errorDiv.classList.remove('hidden');
    errorDiv.textContent = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
    submitBtn.disabled = false;
    submitBtn.textContent = 'Passwort speichern';
  }
});

// Check if already logged in and redirect
(async () => {
  try {
    const response = await fetch('/api/auth.php?action=status');
    const data = await response.json();
    if (data.ok) {
      window.location.href = '/dashboard.php';
    }
  } catch (error) {
    // Silently ignore — not logged in
  }
})();

// Canvas particle animation
(function() {
  const canvas = document.getElementById('bg');
  const ctx = canvas.getContext('2d');
  let W, H, particles;
  function resize() { W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
  function Particle() {
    this.x = Math.random() * W; this.y = Math.random() * H;
    this.vx = (Math.random() - 0.5) * 0.4; this.vy = (Math.random() - 0.5) * 0.4;
    this.alpha = 0.3 + Math.random() * 0.5; this.r = 1.5 + Math.random() * 1.5;
  }
  Particle.prototype.update = function() {
    this.x += this.vx; this.y += this.vy;
    if (this.x < 0 || this.x > W) this.vx *= -1;
    if (this.y < 0 || this.y > H) this.vy *= -1;
  };
  let t = 0;
  function draw() {
    ctx.clearRect(0, 0, W, H); t += 0.005;
    for (let i = 0; i < particles.length; i++) {
      particles[i].update();
      const p = particles[i];
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = `rgba(139,92,246,${p.alpha})`; ctx.fill();
      for (let j = i + 1; j < particles.length; j++) {
        const q = particles[j];
        const dx = p.x - q.x, dy = p.y - q.y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 180) {
          const mx = (p.x + q.x) / 2 + Math.sin(t + i) * 20;
          const my = (p.y + q.y) / 2 + Math.cos(t + j) * 20;
          ctx.beginPath(); ctx.moveTo(p.x, p.y);
          ctx.quadraticCurveTo(mx, my, q.x, q.y);
          ctx.strokeStyle = `rgba(109,40,217,${0.12 * (1 - dist / 180)})`;
          ctx.lineWidth = 0.5; ctx.stroke();
        }
      }
    }
    requestAnimationFrame(draw);
  }
  window.addEventListener('resize', resize);
  resize(); particles = Array.from({length: 90}, () => new Particle()); draw();
})();
</script>

</body>
</html>
