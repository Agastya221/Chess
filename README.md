# Chess Rewards

Russian-only chess student rewards website for a teacher:

- public top-5 leaderboard only;
- private teacher dashboard;
- hidden full student list;
- lesson awards and season points;
- PHP + MySQL, no framework, no Composer.

## Requirements

- PHP 8.2 or newer
- MySQL 5.7+/MariaDB 10.4+
- Apache with `.htaccess` support, or a hosting panel where `public/` can be the web root

## Local/Hosting Setup

1. Create a MySQL database.
2. Copy `.env.example` to `.env`.
3. Fill database values in `.env`.
4. Set `INSTALLER_ENABLED=true` for first setup.
5. Upload the project.
6. Open `/install.php`.
7. Create the first teacher account.
8. Set `INSTALLER_ENABLED=false` in `.env`.

Preferred web root is `public/`. If the host cannot set that, upload the whole project to the site root; the root `.htaccess` routes requests into `public/` and blocks `app/`, `database/`, `.env`, and `README.md`.

## Local Preview With Docker

Run:

```bash
docker compose up --build
```

Then open:

- Public leaderboard: `http://localhost:8080`
- Teacher login: `http://localhost:8080/login.php`
- First setup: `http://localhost:8080/install.php`

The local database is initialized with demo students and awards so the public board has something to show. Create a teacher account through the installer before using the admin dashboard.

To reset the local database:

```bash
docker compose down -v
docker compose up --build
```

## Database

The installer runs:

- `database/schema.sql`
- `database/seed.sql`

You can also run those SQL files manually through phpMyAdmin, then create an admin user with PHP's `password_hash()`.

## Admin Flow

- `/login.php` teacher login
- `/admin.php` dashboard
- `/index.php` public leaderboard

The public page intentionally shows only the configured leaderboard limit, capped at five students. It has no public full-list endpoint and includes `noindex, nofollow`.

## Privacy Defaults

- Use private real name only in admin.
- Use nickname and chess figure publicly.
- Avoid photos, emails, phone numbers, parent contacts, and surnames.
- Archive inactive students instead of deleting them.
