# 🛡️ PhishGuard

Real-time phishing detection for Chrome and Brave.

---

## What it does

PhishGuard analyzes every URL you visit and assigns a **risk score (0–100)** using 11 detection rules. Results are stored in MySQL and visualized in an admin dashboard.

| Level | Score | Meaning |
|---|---|---|
| 🟢 Low | 0–29 | Safe |
| 🟠 Medium | 30–69 | Suspicious |
| 🔴 High | 70–100 | Phishing |

---

## Features

### Extension
- ⚡ Real-time analysis on every tab load
- 🎯 11 detection rules with weighted scoring
- 🎨 Per-tab colored badge (green ✓ / orange ! / red ⚠)
- 🔔 Desktop notification on high-risk sites
- ✅ Trust Site (custom user whitelist)

### Backend
- 🔌 PHP REST API (no framework)
- 🛡️ PDO prepared statements
- 🔒 Bcrypt password hashing

### Dashboard
- 📊 Interactive charts (line + doughnut)
- 🔍 Live search + level filters
- 📥 Export CSV

---

## Stack

- **Extension:** JavaScript (Manifest V3)
- **Backend:** PHP 8
- **Database:** MySQL
- **Environment:** XAMPP
- **Charts:** Chart.js

---

## Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP 8+)
- Chrome or Brave

### 1. Clone

```bash
cd C:\xampp\htdocs
git clone https://github.com/aminearea/phishguard.git
```

### 2. Database

- Open **phpMyAdmin**: http://localhost/phpmyadmin
- Import `database/phishguard.sql`

### 3. Config

- Copy `backend/config.example.php` to `backend/config.php`
- Edit if your MySQL credentials differ (default: `root` / empty)

### 4. Admin password

Generate a bcrypt hash for your desired password:

```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
```

Then in phpMyAdmin, go to database `phishguard`, table `admins`, and run:

```sql
UPDATE admins SET password = '<paste-hash-here>' WHERE username = 'admin';
```

### 5. Extension

1. Open `brave://extensions`
2. Enable **Developer mode**
3. Click **Load unpacked**
4. Select the `extension/` folder

### 6. Dashboard

- URL: http://localhost/phishguard/dashboard/
- Login: `admin` / `admin123`

---

## Detection rules

| # | Rule | Weight |
|---|---|---|
| 1 | No HTTPS | +30 |
| 2 | Uses raw IP address | +30 |
| 3 | Suspicious keyword in URL | +20 |
| 4 | Contains `@` in URL | +20 |
| 5 | Punycode hostname | +25 |
| 6 | Tunneling service (Cloudflare/Ngrok) | +40 |
| 7 | Too many subdomains | +15 |
| 8 | Auto-generated subdomain | +15 |
| 9 | URL shortener | +15 |
| 10 | Very long URL | +10 |
| 11 | HTML file in path | +10 |

Score is capped at 100. Whitelisted domains always return **0**.

---

## API endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/backend/scan.php` | Save a scan |
| `GET` | `/backend/scans.php` | List scans |
| `GET` | `/backend/stats.php` | Statistics |
| `POST` | `/backend/login.php` | Admin login |

---

## Test URLs

| URL | Result |
|---|---|
| `https://google.com` | 🟢 0 |
| `http://neverssl.com` | 🟠 30 |
| `http://192.168.1.1/login.htm` | 🔴 100 |
| `https://xxx.trycloudflare.com/login.html` | 🔴 85 |

---

## Architecture

```
Extension (JS)  →  PHP API  →  MySQL  →  Dashboard (PHP + Chart.js)
```

---

## Project structure

```
phishguard/
├── backend/          PHP REST API
├── dashboard/        Admin panel
├── database/         SQL schema
├── extension/        Chrome/Brave extension
├── LICENSE
└── README.md
```

---

## License

MIT — see [LICENSE](LICENSE).

---

## Author

**Amine** — 3rd year EMI student
