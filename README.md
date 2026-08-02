# SUMAS SmartAttend — Complete System
## AI Student Identity Verification Platform

---

## Project Structure

```
sumas_final/
├── frontend/                  ← Open in browser (or Live Server)
│   ├── index.html             ← Homepage
│   ├── assets/
│   │   ├── css/               ← design-system.css, home.css, pages.css
│   │   ├── js/
│   │   │   ├── api.js         ← API service (talks to Laravel)
│   │   │   ├── app.js         ← Student pages logic
│   │   │   └── admin.js       ← Admin panel logic
│   │   └── images/            ← SUMAS logo, campus gate, VC photo
│   └── pages/
│       ├── login.html         ← Student login
│       ├── register.html      ← Student registration
│       ├── dashboard.html     ← Student dashboard
│       └── admin/
│           ├── login.html     ← Admin login
│           └── dashboard.html ← Admin management panel
│
└── backend/                   ← Laravel 12 REST API
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── AuthController.php      ← Register, Login, Logout
    │   │   │   ├── StudentController.php   ← Dashboard, Profile, Status
    │   │   │   └── AdminController.php     ← Stats, CRUD, CSV Export
    │   │   └── Middleware/
    │   │       └── CheckRole.php           ← Role-based auth
    │   └── Models/
    │       └── User.php                    ← Unified user model
    ├── database/
    │   ├── migrations/                     ← Table definitions
    │   └── seeders/
    │       ├── DatabaseSeeder.php
    │       └── AdminSeeder.php             ← Creates admin account
    ├── routes/
    │   └── api.php                         ← All API routes
    ├── config/
    │   └── cors.php                        ← CORS settings
    ├── .env.example                        ← Copy to .env
    └── composer.json
```

---

## Quick Setup (10 minutes)

### 1. Set up the Laravel Backend

```bash
# Run from the project root (this repo is already the Laravel application)

# Install Laravel dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env — set your database details:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sumas_smartattend
# DB_USERNAME=root
# DB_PASSWORD=yourpassword

# Create the database first:
mysql -u root -p -e "CREATE DATABASE sumas_smartattend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations (creates all tables)
php artisan migrate

# Seed the database (creates admin account)
php artisan db:seed

# Start the Laravel dev server
php artisan serve
# → App + API are now running at http://localhost:8000
```

### 2. Bootstrap / middleware

`bootstrap/app.php` is already present and configured for Laravel 12
(role middleware alias, Sanctum stateful API, JSON API error handling).

### 3. Open the Frontend

The frontend is served by Laravel itself as Blade views
(converted from the original `frontend/` static pages):

| Page | URL |
|------|-----|
| Home | `http://localhost:8000/` |
| Student login | `http://localhost:8000/login` |
| Student registration | `http://localhost:8000/register` |
| Student dashboard | `http://localhost:8000/dashboard` |
| Admin login | `http://localhost:8000/admin/login` |
| Admin dashboard | `http://localhost:8000/admin/dashboard` |

The API base URL is same-origin (`/api`) — configured in `public/assets/js/api.js`.
The original static pages remain in `frontend/` for reference.

---

## Default Credentials

### Admin Login
- **URL:** `http://localhost:8000/admin/login`
- **Username:** `admin`
- **Password:** `sumas@admin2025`

_(Set via `.env` `ADMIN_USERNAME` and `ADMIN_PASSWORD` before seeding)_

### Student Login
Students create their own credentials during registration.
No demo accounts — real registration required.

---

## API Reference

### Public Endpoints
| Method | URL | Description |
|--------|-----|-------------|
| POST | `/api/auth/register` | Student registration |
| POST | `/api/auth/login` | Student login |
| POST | `/api/auth/admin-login` | Admin login |
| GET  | `/api/student/status?matric=SUMAS/CS/2023/001` | Public status check (matric as query param) |

### Student Endpoints (Bearer token required)
| Method | URL | Description |
|--------|-----|-------------|
| GET  | `/api/auth/me` | Current user info |
| POST | `/api/auth/logout` | Logout |
| GET  | `/api/student/dashboard` | Dashboard data |
| PUT  | `/api/student/profile` | Update profile |

### Admin Endpoints (Admin Bearer token required)
| Method | URL | Description |
|--------|-----|-------------|
| GET    | `/api/admin/stats` | Overview statistics |
| GET    | `/api/admin/students` | All students (with `?status=Pending`) |
| GET    | `/api/admin/students/{id}` | Single student |
| PUT    | `/api/admin/students/{id}/status` | Approve/Reject |
| DELETE | `/api/admin/students/{id}` | Delete student |
| GET    | `/api/admin/export` | Download CSV |

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Vanilla JavaScript (ES2022) |
| Backend | PHP 8.2 + Laravel 12 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Database | MySQL 8+ (or SQLite for dev) |
| Fonts | Inter (Google Fonts) |
| Design | Custom premium academic design system |

---

## Security Notes

- All passwords are hashed with `bcrypt` via Laravel's `Hash::make()`
- API routes are protected with Sanctum bearer tokens
- Role middleware (`role:student` / `role:admin`) prevents cross-role access
- CORS is configured — restrict `allowed_origins` in production
- SQL injection: all queries use Eloquent (parameterized)
- XSS: frontend escapes all user-generated content before inserting into DOM

---

## Admin Features

- **Overview:** Total, Pending, Approved, Rejected, Verified counts + recent table + dept chart
- **Student Management:** Searchable, sortable table with filter tabs
- **Student Detail Modal:** Full profile view with Approve / Reject / Delete actions
- **Bulk Approve:** Approve all pending registrations in one click
- **CSV Export:** Download all student data as `.csv`
- **Settings:** Admin password management

---

*SUMAS SmartAttend — State University of Medical and Applied Sciences, Igbo Eno, Enugu State, Nigeria*
