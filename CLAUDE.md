# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Rezept-Wochenplaner** (Recipe Week Planner) is a browser-based meal planning application written in German with a **PHP backend + MySQL database**. It lets users:

- Log in securely with username/password
- Create and manage recipes (shared family recipe book)
- Generate random weekly meal plans (per-user)
- Build automated shopping lists with quantity aggregation and smart purchase suggestions
- Export data as JSON for backup

All data persists in **MySQL** — no browser localStorage. The app is deployed to Strato shared hosting via GitHub Actions.

## Architecture & Code Organization

### Multi-Tier Architecture

```
Browser (HTML/JS/Tailwind CSS)
    ↓ HTTP + CSRF token
PHP API Layer (/api/*.php)
    ↓ PDO Prepared Statements
MySQL Database (3 tables: users, recipes, week_plan_entries)
```

### File Structure

```
/htdocs/
├── index.php              ← Main app (HTML + JS, requires PHP session)
├── login.html             ← Login page (standalone, no session required)
│
├── api/
│   ├── auth.php           ← Authentication (login/logout/status)
│   ├── recipes.php        ← Recipes CRUD (shared, all users see same recipes)
│   └── weekplan.php       ← Per-user week plan (each user has independent plan)
│
├── admin/
│   ├── index.php          ← Admin dashboard (user management UI)
│   ├── users.php          ← User CRUD actions (create/delete/reset password)
│   └── .htaccess          ← Directory protection
│
├── includes/
│   ├── db.php             ← PDO connection singleton
│   ├── auth_check.php     ← Session & CSRF validation guard
│   ├── config.php         ← DB credentials (NOT in git, manually created on server)
│   ├── config.php.example ← Template for config.php
│   └── .htaccess          ← Prevent direct HTTP access
│
├── .github/workflows/
│   └── deploy.yml         ← CI/CD: Push to main → SFTP to Strato
│
├── schema.sql             ← Database schema (run once on server)
└── .gitignore             ← Excludes config.php, IDE files, etc.
```

## Data Flow

### 1. **Authentication**
- User visits `/login.html`, enters username/password
- Frontend `POST /api/auth.php` with credentials
- PHP validates password hash, creates session, returns CSRF token in `$_SESSION['csrf_token']`
- Frontend redirects to `/index.php`

### 2. **Index.php Page Load**
- PHP at top: session check + CSRF token in `<meta name="csrf-token">`
- JS `init()` checks auth status via `/api/auth.php?action=status`
- If not authenticated, redirects to `/login.html`
- If authenticated, fetches recipes + week plan, shows user badge, renders UI

### 3. **Recipe CRUD**
- **GET** `/api/recipes.php` → fetch all recipes (shared)
- **POST** `/api/recipes.php` → create recipe (JSON body: `{id, name, ingredients}`)
- **PUT** `/api/recipes.php` → update recipe
- **DELETE** `/api/recipes.php` → delete recipe (cascades to week_plan_entries)
- All modifying requests include `X-CSRF-Token` header

### 4. **Week Plan**
- **GET** `/api/weekplan.php` → fetch current user's 7-day plan
- **POST** `/api/weekplan.php` → save entire plan (upsert, `{weekPlan: [{day, recipeId}, ...]}`)
- Each user has independent plan stored in `week_plan_entries` table

### 5. **Admin Panel**
- Admin user logs in, navigates to `/admin/index.php`
- Lists all users, create/delete/reset password
- **POST** `/admin/users.php` with actions: `create`, `delete`, `reset_password`
- All changes immediately reflected in database

## Frontend (JavaScript)

### Key Functions

| Function | Purpose |
|---|---|
| `init()` | Startup: auth check, load data, render UI |
| `loadData()` | Async fetch recipes + week plan from API |
| `saveRecipe()` | Async POST/PUT to `/api/recipes.php` |
| `deleteRecipe()` | Async DELETE to `/api/recipes.php` |
| `saveWeekPlan()` | Async POST to `/api/weekplan.php` |
| `renderRecipes()` | Display recipe cards |
| `renderWeekPlan()` | Display 7-day grid |
| `renderShoppingList()` | Aggregate ingredients, categorize, display |
| `logout()` | POST logout, redirect to login |

### CSRF Protection
- Meta tag: `<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">`
- JS reads token: `const csrfToken = document.querySelector('meta[name=csrf-token]').content`
- Helper: `fetchWithCSRF(url, options)` adds `X-CSRF-Token` header to all non-GET requests
- API validates: `auth_check.php` checks header matches `$_SESSION['csrf_token']`

### Data Binding
- Recipes/weekPlan held in `recipes[]` and `weekPlan[]` global arrays
- No framework state management — vanilla DOM updates
- Shopping list is **derived/computed**, not stored (aggregated from recipes + week plan in browser)

## Backend (PHP)

### Database Schema

**`users`** table
```sql
id (auto), username (unique), display_name, password (bcrypt),
role (admin|user), created_at, last_login
```

**`recipes`** table
```sql
id (auto), client_id (unique), name, ingredients, created_by (FK users.id),
created_at, updated_at
```
Note: `client_id` preserves frontend IDs (`"def-1"`, timestamp strings). Frontend never sees integer `id`.

**`week_plan_entries`** table
```sql
id (auto), user_id (FK users.id), day_name, day_index (0-6), recipe_id (FK recipes.client_id),
updated_at
UNIQUE KEY (user_id, day_index)
```

### API Endpoints

| Method | Path | Query/Body | Response |
|---|---|---|---|
| GET | `/api/auth.php?action=status` | — | `{ok, user}` or `{ok: false}` |
| POST | `/api/auth.php` | `{action:"login", username, password}` | `{ok, user}` or `{ok: false, error}` |
| POST | `/api/auth.php` | `{action:"logout"}` | `{ok: true}` |
| GET | `/api/recipes.php` | — | `{ok: true, recipes: [{id, name, ingredients}...]}` |
| POST | `/api/recipes.php` | `{id, name, ingredients}` | `{ok: true, recipe: {...}}` or error |
| PUT | `/api/recipes.php` | `{id, name, ingredients}` | `{ok: true}` or error |
| DELETE | `/api/recipes.php` | `{id}` | `{ok: true}` or error |
| GET | `/api/weekplan.php` | — | `{ok: true, weekPlan: [{day, recipeId}...]}` |
| POST | `/api/weekplan.php` | `{weekPlan: [{day, recipeId}...]}` | `{ok: true}` or error |
| POST | `/admin/users.php` | `{action:"create", username, display_name, password, role}` | `{ok: true, user: {...}}` |
| POST | `/admin/users.php` | `{action:"delete", user_id}` | `{ok: true}` or error |
| POST | `/admin/users.php` | `{action:"reset_password", user_id, new_password}` | `{ok: true}` or error |

### Security

- **Password hashing**: `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`; verify with `password_verify()`
- **SQL Injection**: All queries use PDO prepared statements with bound parameters — ZERO string interpolation
- **Session security**:
  - `session.cookie_httponly = 1` (prevent JS access to session cookie)
  - `session.cookie_samesite = Strict` (prevent CSRF)
  - `session.cookie_secure = 1` (HTTPS only, set when HTTPS confirmed on Strato)
  - Session ID regenerated on successful login
- **CSRF**: Token in `$_SESSION`, embedded in HTML, sent as `X-CSRF-Token` header, validated on all state-changing requests
- **Access control**:
  - Every API endpoint includes `requires '../includes/auth_check.php'` (redirects to login if not authenticated)
  - Admin endpoints additionally check `$_SESSION['role'] === 'admin'`
  - Week plan queries always filtered by `user_id = $_SESSION['user_id']` (users cannot access each other's plans)
- **Input validation**: Usernames, recipe names, day names validated against whitelist; lengths enforced
- **Directory protection**: `includes/.htaccess` prevents direct HTTP access to `config.php`, `db.php`, etc.

## Development & Testing

### Prerequisites
1. Local PHP 8.0+ environment OR use Strato's PHP directly
2. MySQL client to view/query database
3. Git and GitHub account

### Testing Locally

You cannot fully test locally without MySQL. But you can:
- Check PHP syntax: `php -l index.php`
- Inspect frontend code: just open files in editor

### Testing on Strato

1. Create database + tables using schema.sql via Strato's phpMyAdmin
2. Create `includes/config.php` with your database credentials (copy from `config.php.example`)
3. Insert admin user with bcrypt-hashed password (see schema.sql comments)
4. Open `https://yoursite.com/` in browser → redirects to login
5. Test login with admin credentials
6. Create recipe, generate week plan, check shopping list
7. Visit `/admin/` to create additional users

### Manual Testing Checklist

- [ ] Login works, session persists
- [ ] Logout clears session
- [ ] Create recipe → appears for all logged-in users
- [ ] Edit recipe → updates globally
- [ ] Delete recipe → removes from others' week plans too
- [ ] Suggest week plan → randomly generates 7 recipes
- [ ] Change day → replaces one day with random recipe
- [ ] Shopping list aggregates quantities correctly
- [ ] Two users logged in simultaneously have independent week plans
- [ ] Admin can create user
- [ ] Admin can reset user password
- [ ] Admin can delete user
- [ ] CSRF token validation works (POST without token = 403)

## Deployment

### Workflow
1. Commit changes to git (never include `includes/config.php`)
2. Push to `main` branch
3. GitHub Actions triggers `.github/workflows/deploy.yml`
4. `lftp mirror` syncs files to `/htdocs/` on Strato via SFTP
5. `includes/config.php` is **NOT** mirrored (it's in `.gitignore`)

### Manual Steps Required (First Time Setup)

1. Create MySQL database on Strato (via control panel)
2. Run `schema.sql` via phpMyAdmin to create tables
3. Create `includes/config.php` on server manually (DO NOT commit) with credentials
4. Insert first admin user via `schema.sql` comments or phpMyAdmin
5. Set PHP version to 8.x in Strato control panel (if not already)

### Secrets Required (GitHub Repository Settings)

- `SFTP_USER`: Strato SFTP username
- `SFTP_PASS`: Strato SFTP password
- `SFTP_HOST`: Strato server hostname (e.g., `ftp.example.com`)

## Notable Implementation Details

### Why `client_id` in recipes?
Frontend generates IDs as strings (`"def-1"` or `Date.now().toString()`). Database preserves these in `client_id` so frontend never needs to know about integer `id`. API responses use `id: client_id`.

### Why full week plan upsert?
Easier for frontend: `suggestWeek()` and `changeDay()` just mutate local `weekPlan[]` array and POST entire plan. No per-day PATCH logic needed.

### Why no import/import on new version?
Data is server-side now. Import from old exported JSON is removed (creates confusion). Export is kept for backup purposes.

### Why logout button in header?
Users need a way to switch accounts. Logout button calls `/api/auth.php` with `action: logout`, clears session server-side, redirects to login.

## Common Development Tasks

### To add a new feature to recipes
1. Add DB column if needed (migration is manual on Strato — update schema.sql comments)
2. Update `api/recipes.php` to read/write the column
3. Update `index.php` JS: modify recipe form + rendering

### To add a new category to shopping list
1. Add keywords to `CATEGORY_KEYWORDS` object in `index.php`

### To increase session timeout
1. Edit `php.ini` on Strato (usually via control panel) or add to `.htaccess`:
   ```apache
   php_value session.gc_maxlifetime 3600
   ```

### To regenerate all user passwords
1. Go to `/admin/`, use "Passwort zurücksetzen" for each user
2. Or write a PHP script that updates multiple passwords at once

## Known Limitations

- No notification/email when password is reset
- No password change UI for regular users (only admin can reset)
- No recipe search/filtering (simple list only)
- No meal preferences/allergies (global recipe book only)
- No sync across devices (per-user session only)

## Future Enhancements

- User password change form (`/user/change-password.php`)
- Recipe search + filtering
- Ingredient-based meal suggestions (KI integration)
- Email notifications on shared events
- Multi-device sync via cloud API
- Dietary restriction tags on recipes

---

**Last Updated:** 2026-03-28
**Deployment:** Strato shared hosting (PHP 8.x + MySQL 5.7+)
**Frontend:** Vanilla HTML/JS + Tailwind CSS (no framework)
