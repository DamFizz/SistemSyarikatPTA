# SEMS — Smart Employee Management System

An integrated web-based platform for attendance (geofencing + selfie + QR verification), payroll, leave, overtime, employee management, IT helpdesk, announcements and reporting. Built as a Final Year Project.

## Tech Stack

- **Backend:** Laravel (PHP 8.3+)
- **Frontend:** Blade + Tailwind CSS + Alpine.js
- **Database:** MySQL
- **PDF:** barryvdh/laravel-dompdf
- **QR Code:** bacon/bacon-qr-code (pure PHP, no GD/Imagick required)

## Requirements

- PHP 8.3 or later with `pdo_mysql` extension
- Composer
- MySQL 5.7+ / 8.0+ (or MariaDB)
- Node.js 18+ and npm

## Local Setup (WAMP / XAMPP / any local server)

1. Clone or copy the project into your server's web root (e.g. `wamp64/www/SistemSyarikat`).

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy the environment file and generate an application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Create a MySQL database named `sems` (or update `DB_DATABASE` in `.env` to match your own).

5. Update `.env` with your database credentials and app URL:
   ```
   APP_URL=http://localhost/SistemSyarikat/public
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sems
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   > **Note:** if your MySQL server's default storage engine is MyISAM instead of InnoDB, this is already handled — `config/database.php` forces `'engine' => 'InnoDB'` for the `mysql` connection so migrations succeed regardless of server defaults.

6. Run migrations and seed demo data:
   ```bash
   php artisan migrate --seed
   ```

7. Link the public storage disk (needed for selfies, attachments and profile photos):
   ```bash
   php artisan storage:link
   ```

8. Build frontend assets:
   ```bash
   npm run build
   ```

9. Visit `http://localhost/SistemSyarikat/public` in your browser (or run `php artisan serve` for a quick local preview at `http://127.0.0.1:8000`).

## Demo Accounts

All seeded accounts use the password `password`.

| Role | Email |
|---|---|
| Super Admin | superadmin@sems.test |
| HR / Admin | hradmin@sems.test |
| Manager | manager@sems.test |
| Technician | technician@sems.test |
| Employee | employee@sems.test |

## Running Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`) and never touch your MySQL development database.

## Attendance Verification Flow

Clock-in requires, in order:
1. **GPS verification** — browser geolocation compared against the employee's assigned office (Haversine distance, configurable radius per office).
2. **Selfie capture** — camera-only (no gallery upload), stored under `storage/app/public/selfies`.
3. **QR checkpoint scan** — a rotating QR code (default 60s expiry) displayed on the HR/Super Admin "QR Checkpoint Display" screen, scanned live via the device camera (`jsQR`).

Clock-out requires GPS verification and automatically calculates working hours and flags potential overtime for approval.

## Deploying to Railway

Railway supports Laravel + PHP 8.3 + MySQL out of the box via Nixpacks, and every Railway project gets a free HTTPS domain — which the camera/GPS features in this app require. `nixpacks.toml` in the project root already configures the build for you.

1. **Push this project to GitHub** (Railway deploys from a GitHub repo). If this folder isn't a git repo yet:
   ```bash
   git init
   git add .
   git commit -m "Initial commit"
   ```
   Then create a new empty repo on GitHub and push:
   ```bash
   git remote add origin https://github.com/<your-username>/<repo-name>.git
   git branch -M main
   git push -u origin main
   ```

2. **Create the Railway project**
   - Go to [railway.app](https://railway.app) → Sign in with GitHub → **New Project** → **Deploy from GitHub repo** → select your repo.

3. **Add a MySQL database**
   - In the same Railway project: **+ New** → **Database** → **Add MySQL**.
   - Railway automatically creates env vars like `MYSQLHOST`, `MYSQLPORT`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE` on the MySQL service.

4. **Add a persistent Volume** (critical — without this, uploaded selfies/attachments are wiped on every redeploy)
   - On your app service → **Settings** → **Volumes** → **+ New Volume**.
   - Mount path: `/app/storage/app/public`

5. **Set environment variables** on your app service (**Variables** tab). Add each of these — for the `DB_*` ones, use Railway's variable-reference syntax so they always match the MySQL service:
   ```
   APP_NAME=SEMS
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=                       (generate locally: php artisan key:generate --show, paste the base64:... value here)
   APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   ```
   (Replace `MySQL` in the `${{MySQL.XXXX}}` references with whatever name Railway gave your database service, if different.)

6. **Deploy** — Railway auto-deploys on every push to `main`. The start command in `nixpacks.toml` automatically runs migrations, caches config, and starts the server on every deploy — no manual SSH step needed (Railway doesn't offer shell access on the free tier anyway).

7. **Seed demo data (one-time only)** — Railway's free tier has no persistent shell, so run this from your own terminal using the [Railway CLI](https://docs.railway.com/guides/cli):
   ```bash
   npm i -g @railway/cli
   railway login
   railway link          # select your project
   railway run php artisan db:seed
   ```

8. Visit the domain Railway gives you (**Settings → Networking → Generate Domain**, or your own custom domain) — it will be HTTPS by default, so clock-in's camera/GPS/QR flow works immediately on real phones.

> **Note on the free tier:** Railway's free/trial plan runs on usage credits, not unlimited hours — fine for FYP demo purposes, but check current limits at [railway.app/pricing](https://railway.app/pricing) if you need it running continuously for an extended period.

## Production Deployment Checklist

Before deploying to a live server:

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Set a strong, unique `APP_KEY` (`php artisan key:generate` if not already set)
- [ ] Use real HTTPS `APP_URL` (camera/geolocation APIs require a secure context in production browsers — `localhost` is exempted for development only)
- [ ] Run `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`
- [ ] Run `npm run build` (not `npm run dev`)
- [ ] Point the web server's document root at the `public/` folder, not the project root
- [ ] Change all seeded demo account passwords or remove the seeder from production data
- [ ] Configure a real mail driver if password-reset emails are needed
- [ ] Set up a scheduled backup for the MySQL database

## Project Structure Notes

- Controllers, views and routes are organized per role: `SuperAdmin`, `HRAdmin`, `Manager`, `Technician`, `Employee` (see `app/Http/Controllers/`, `resources/views/`, `routes/modules/`).
- Role-based access is enforced via the `role` middleware (`app/Http/Middleware/EnsureUserHasRole.php`).
- Every create/update/approve/reject action across modules is recorded in the audit log (`App\Models\AuditLog::record()`), viewable by Super Admin only.
- Business logic for attendance and payroll calculations lives in `app/Services/` (`AttendanceService`, `PayrollService`) rather than in controllers, so it can be unit-tested independently.
