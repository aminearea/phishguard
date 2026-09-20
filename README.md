# 🛡️ PhishGuard

A Chrome extension that detects phishing URLs using security rules and
reports results to a PHP + MySQL backend with a small admin dashboard.

## Stack
- Chrome Extension: HTML, CSS, JavaScript (Manifest V3)
- Backend: PHP (no framework)
- Database: MySQL
- Environment: XAMPP

## Setup

1. Copy the folder `phishguard/` into `C:\xampp\htdocs\`
2. Start Apache + MySQL from XAMPP
3. Open phpMyAdmin → Import `database/phishguard.sql`
4. (Optional) Update the admin password hash:
   - Create a temp file `hash.php` with: `<?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>`
   - Run it at `http://localhost/phishguard/hash.php`
   - Copy the hash → paste into the `admins` table via phpMyAdmin
   - Delete `hash.php`
5. Open Chrome → `chrome://extensions` → enable **Developer mode**
6. Click **Load unpacked** → select the `extension/` folder
7. Open the dashboard: `http://localhost/phishguard/dashboard/`
   - Login: `admin` / `admin123`

## How it works

1. User opens a website
2. Extension analyzes the URL with 8 rules
3. Computes a risk score (0–100) → Low / Medium / High
4. Sends the result to `backend/scan.php`
5. Admin sees everything in the dashboard

## API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `backend/scan.php`  | Save a scan |
| GET  | `backend/scans.php` | List scans |
| GET  | `backend/stats.php` | Counters |
| POST | `backend/login.php` | Admin login |

## Default credentials
- Username: `admin`
- Password: `admin123`