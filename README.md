# 📅 Rezept-Wochenplaner

A minimal, secure meal planning application with shared recipe management and per-user weekly meal plans.

## Features

✨ **Core Features**
- 🔐 **Secure Authentication** — Bcrypt password hashing, CSRF protection, session management
- 📖 **Shared Recipe Book** — Family-wide recipe library with ingredient lists
- 📋 **Weekly Meal Planning** — Per-user customizable 7-day meal plans
- 🛒 **Smart Shopping Lists** — Auto-aggregated ingredients with quantity tracking
- 📥 **Data Export** — Backup plans and recipes as JSON
- 👥 **User Management** — Admin panel to create/manage users
- ✅ **Admin Approval** — New users require admin sign-off before account activation

🎨 **Design**
- **Minimal & Mysterious** — Clean interface that doesn't immediately reveal app purpose
- **Starfield Animation** — Smooth canvas-based particle system with animated Bézier curves
- **Dark Theme** — Zinc/purple color scheme with accessibility in mind
- **Responsive** — Works on desktop and tablet (mobile-optimized)

## Quick Start

### Prerequisites
- PHP 8.0+ (via Strato shared hosting or local env)
- MySQL 5.7+ database
- No npm/build step required — vanilla HTML/JS/CSS

### Local Setup (Minimal Testing)
```bash
git clone https://github.com/megabashment/website.git
cd website
php -l index.php  # Check syntax
```

### Production Setup (Strato)
See [CLAUDE.md](./CLAUDE.md) for detailed setup instructions.

**Quick checklist:**
1. Create MySQL database on Strato control panel
2. Run `schema.sql` via phpMyAdmin to create tables
3. Create `includes/config.php` with your DB credentials (copy from `config.php.example`)
4. Insert first admin user via `schema.sql` comments
5. Push to `main` branch — GitHub Actions deploys automatically

## Project Structure

```
/
├── index.php                 ← Main app (requires session)
├── login.html                ← Login page
├── register.html             ← User registration
├── forgot-password.html      ← Password recovery
├── reset-password.php        ← Reset token handler
│
├── api/
│   ├── auth.php              ← Auth endpoints (login, logout, register)
│   ├── recipes.php           ← Recipe CRUD (shared for all users)
│   └── weekplan.php          ← Per-user meal planning
│
├── admin/
│   ├── index.php             ← User management dashboard
│   ├── users.php             ← User CRUD actions
│   └── .htaccess             ← Directory protection
│
├── includes/
│   ├── db.php                ← PDO connection singleton
│   ├── auth_check.php        ← Session & CSRF middleware
│   ├── mailer.php            ← Email sending (optional)
│   ├── config.php            ← DB credentials (NOT in git)
│   ├── config.php.example    ← Config template
│   ├── phpmailer/            ← Email library
│   └── .htaccess             ← Prevent HTTP access
│
├── .github/workflows/
│   └── deploy.yml            ← CI/CD pipeline (push → deploy)
│
├── schema.sql                ← Database schema
├── CLAUDE.md                 ← Developer guide
└── README.md                 ← This file
```

## Data Model

### Users Table
```sql
id, username (unique), display_name, email, password (bcrypt), role (admin|user),
status (pending|active), reset_token, reset_token_expires, created_at, last_login
```

### Recipes Table
```sql
id, client_id (unique, preserves frontend ID), name, ingredients (text),
created_by (FK users.id), created_at, updated_at
```

### Week Plan Entries Table
```sql
id, user_id (FK users.id), day_name, day_index (0-6), recipe_id (FK recipes.client_id),
updated_at
UNIQUE KEY (user_id, day_index)
```

## API Endpoints

### Authentication
| Method | Endpoint | Body | Purpose |
|--------|----------|------|---------|
| GET | `/api/auth.php?action=status` | — | Check login status |
| POST | `/api/auth.php` | `{action: "login", username, password}` | Log in user |
| POST | `/api/auth.php` | `{action: "logout"}` | Log out user |
| POST | `/api/auth.php` | `{action: "register", username, email, password}` | Register new account (pending) |
| POST | `/api/auth.php` | `{action: "forgot_password", username}` | Request password reset email |
| POST | `/api/auth.php` | `{action: "confirm_reset", token, new_password}` | Reset password with token |

### Recipes
| Method | Endpoint | Body | Purpose |
|--------|----------|------|---------|
| GET | `/api/recipes.php` | — | Get all recipes |
| POST | `/api/recipes.php` | `{id, name, ingredients}` | Create recipe |
| PUT | `/api/recipes.php` | `{id, name, ingredients}` | Update recipe |
| DELETE | `/api/recipes.php` | `{id}` | Delete recipe |

### Meal Planning
| Method | Endpoint | Body | Purpose |
|--------|----------|------|---------|
| GET | `/api/weekplan.php` | — | Get current user's week plan |
| POST | `/api/weekplan.php` | `{weekPlan: [{day, recipeId}, ...]}` | Save entire week plan |

### Admin (requires admin role)
| Method | Endpoint | Body | Purpose |
|--------|----------|------|---------|
| POST | `/admin/users.php` | `{action: "create", username, display_name, email, password, role}` | Create user |
| POST | `/admin/users.php` | `{action: "delete", user_id}` | Delete user |
| POST | `/admin/users.php` | `{action: "reset_password", user_id, new_password}` | Reset user password |
| POST | `/admin/users.php` | `{action: "approve", user_id}` | Approve pending user |
| POST | `/admin/users.php` | `{action: "reject", user_id}` | Reject pending user |

All POST/PUT/DELETE requests require `X-CSRF-Token` header.

## Security

- ✅ Password hashing: `PASSWORD_BCRYPT` with cost 12
- ✅ SQL injection prevention: PDO prepared statements (zero string interpolation)
- ✅ CSRF protection: Server-generated token in session, client sends via header
- ✅ Session hardening: httponly, SameSite=Strict, secure (HTTPS)
- ✅ Access control: Every endpoint validates user session and permissions
- ✅ Directory protection: `.htaccess` prevents direct HTTP access to `includes/config.php`
- ✅ Input validation: Regex whitelist for usernames, length limits, type validation

## Development

See [CLAUDE.md](./CLAUDE.md) for:
- Local testing checklist
- Common development tasks
- Manual testing procedures
- Known limitations
- Future enhancements

## Deployment

This project uses GitHub Actions for automated deployment:

1. **Trigger:** Push to `main` branch
2. **CI/CD:** `.github/workflows/deploy.yml` runs
3. **Deploy:** Files synced to Strato via SFTP
4. **Secrets:** Requires GitHub repository secrets:
   - `SFTP_USER` — Strato username
   - `SFTP_PASS` — Strato password
   - `SFTP_HOST` — Strato server

⚠️ **Important:** `includes/config.php` is NOT deployed (in `.gitignore`) — must be created manually on server with your DB credentials.

## Tech Stack

- **Backend:** PHP 8.0+ with PDO
- **Database:** MySQL 5.7+
- **Frontend:** Vanilla HTML/JavaScript (no framework)
- **Styling:** Tailwind CSS (via CDN)
- **Hosting:** Strato shared hosting

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Canvas API required for starfield animation
- ES6+ JavaScript features used

## License

Private project. See source for usage terms.

---

**Last Updated:** 2026-03-28
**Repository:** [megabashment/website](https://github.com/megabashment/website)
**Status:** Active development
