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
    body { margin: 0; padding: 0; overflow: hidden; }
    #bg { position: fixed; top: 0; left: 0; z-index: 0; display: block; }
    #reset-container { position: relative; z-index: 1; }
    input:focus { outline: none; }
    .glowing-button {
      transition: all 0.3s ease;
    }
    .glowing-button:hover {
      box-shadow: 0 0 20px rgba(124, 58, 237, 0.4), 0 0 40px rgba(124, 58, 237, 0.2);
    }
  </style>
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">

<canvas id="bg"></canvas>

<div id="reset-container" class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="backdrop-blur-sm">

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

        <button
          type="submit"
          class="glowing-button w-full mt-8 px-5 py-2.5 bg-violet-600 text-white rounded-lg text-sm font-semibold transition-colors"
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
</div>

<script>
// Canvas starfield animation
const canvas = document.getElementById('bg');
const ctx = canvas.getContext('2d');

canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

// Particle system
const particles = [];
const particleCount = 90;
const connectionDistance = 180;

class Particle {
  constructor() {
    this.x = Math.random() * canvas.width;
    this.y = Math.random() * canvas.height;
    this.vx = (Math.random() - 0.5) * 0.5;
    this.vy = (Math.random() - 0.5) * 0.5;
    this.radius = Math.random() * 1.5 + 0.5;
  }

  update() {
    this.x += this.vx;
    this.y += this.vy;

    // Bounce at edges
    if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
    if (this.y < 0 || this.y > canvas.height) this.vy *= -1;

    // Clamp position
    this.x = Math.max(0, Math.min(canvas.width, this.x));
    this.y = Math.max(0, Math.min(canvas.height, this.y));
  }

  draw() {
    ctx.fillStyle = `rgba(139, 92, 246, 0.8)`;
    ctx.beginPath();
    ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
    ctx.fill();
  }
}

// Initialize particles
for (let i = 0; i < particleCount; i++) {
  particles.push(new Particle());
}

function drawConnections() {
  for (let i = 0; i < particles.length; i++) {
    for (let j = i + 1; j < particles.length; j++) {
      const dx = particles[i].x - particles[j].x;
      const dy = particles[i].y - particles[j].y;
      const distance = Math.sqrt(dx * dx + dy * dy);

      if (distance < connectionDistance) {
        const opacity = (1 - distance / connectionDistance) * 0.15;
        ctx.strokeStyle = `rgba(109, 40, 217, ${opacity})`;
        ctx.lineWidth = 1;

        // Bezier curve with animated control point
        const mx = (particles[i].x + particles[j].x) / 2;
        const my = (particles[i].y + particles[j].y) / 2;
        const offset = Math.sin(Date.now() * 0.0005) * 5;
        const cpx = mx + offset * (dy / distance || 1);
        const cpy = my + offset * (dx / distance || 1);

        ctx.beginPath();
        ctx.moveTo(particles[i].x, particles[i].y);
        ctx.quadraticCurveTo(cpx, cpy, particles[j].x, particles[j].y);
        ctx.stroke();
      }
    }
  }
}

function animate() {
  // Clear canvas with fade effect
  ctx.fillStyle = 'rgba(9, 9, 11, 0.1)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Update and draw particles
  for (let particle of particles) {
    particle.update();
    particle.draw();
  }

  // Draw connections
  drawConnections();

  requestAnimationFrame(animate);
}

animate();

// Resize handler
window.addEventListener('resize', () => {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
});

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
