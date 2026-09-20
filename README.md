<div align="center">

<img src="extension/icons/icon128.png" alt="PhishGuard Logo" width="120" />

# PhishGuard

**Real-time phishing detection for Chrome and Brave.**

Analyzes every URL you visit, scores it 0–100, and warns you **before** you enter data on a phishing page.

<br/>

[![MIT License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)
[![Manifest V3](https://img.shields.io/badge/Manifest-V3-blue?style=for-the-badge&logo=googlechrome&logoColor=white)](https://developer.chrome.com/docs/extensions/mv3/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Made in Morocco](https://img.shields.io/badge/Made%20in-Morocco-red?style=for-the-badge)](https://github.com/aminearea)

<br/>

[**Features**](#-features) · [**Screenshots**](#-screenshots) · [**Installation**](#-installation) · [**Architecture**](#-architecture) · [**Detection Rules**](#-detection-rules) · [**API**](#-api-endpoints)

</div>

---

## 📌 Overview

**PhishGuard** is a browser extension that protects users from phishing attacks by analyzing URLs in **real time**. Every page you visit is evaluated against **11 security rules** and given a risk score from **0 (safe)** to **100 (phishing)**.

Unlike simple blacklist-based tools, PhishGuard detects **emerging phishing patterns** like Cloudflare tunnels, IP-based pages, and punycode domains — even when the site hasn't been reported yet.

<table>
<tr>
<td width="33%" align="center">
<h3>🟢 Low</h3>
<b>Score 0–29</b><br/>
<sub>Safe to browse</sub>
</td>
<td width="33%" align="center">
<h3>🟠 Medium</h3>
<b>Score 30–69</b><br/>
<sub>Proceed with caution</sub>
</td>
<td width="33%" align="center">
<h3>🔴 High</h3>
<b>Score 70–100</b><br/>
<sub>Do not enter data</sub>
</td>
</tr>
</table>

---

## 📸 Screenshots

<table>
<tr>
<td align="center" width="33%">
<img src="docs/screenshots/safe.png" alt="Safe site" /><br/>
<b>🟢 Safe Site</b><br/>
<sub>Whitelisted domain — score 0</sub>
</td>
<td align="center" width="33%">
<img src="docs/screenshots/phishing.png" alt="Phishing detected" /><br/>
<b>🔴 Phishing Detected</b><br/>
<sub>Score 100 — 5 rules triggered</sub>
</td>
<td align="center" width="33%">
<img src="docs/screenshots/dashboard.png" alt="Dashboard" /><br/>
<b>📊 Admin Dashboard</b><br/>
<sub>Charts, filters, CSV export</sub>
</td>
</tr>
</table>

---

## ✨ Features

<table>
<tr>
<td width="50%" valign="top">

### 🧩 Extension
- ⚡ **Real-time analysis** on every tab load
- 🎯 **11 detection rules** with weighted scoring
- 🎨 **Per-tab colored badge** (✓ / ! / ⚠)
- 🔔 **Desktop notifications** on high-risk sites
- 🔊 **Audio alert** for confirmed threats
- ✅ **Trust Site** — custom user whitelist
- 📋 **Copy URL / JSON** for sharing
- 🌐 **45+ trusted domains** built in

</td>
<td width="50%" valign="top">

### ⚙️ Backend
- 🔌 **PHP REST API** (no framework)
- 🛡️ **PDO prepared statements**
- 🔒 **Bcrypt password hashing**
- ✅ **Input validation** with `filter_var`
- 🌍 **CORS** headers for extension

### 📊 Dashboard
- 📈 **Interactive charts** (line + doughnut)
- 🔍 **Live search** + level filters
- 📥 **Export CSV** for reports
- 📄 **Pagination** (50 per page)
- 🎨 **Dark theme** with yellow accent

</td>
</tr>
</table>

---

## 🚀 Installation

### Prerequisites

![XAMPP](https://img.shields.io/badge/XAMPP-Required-FB7A24?style=flat-square)
![Chrome](https://img.shields.io/badge/Chrome%2FBrave-Required-4285F4?style=flat-square&logo=googlechrome&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)

### 1️⃣ Clone

```bash
cd C:\xampp\htdocs
git clone https://github.com/aminearea/phishguard.git
cd phishguard
```

### 2️⃣ Import database

- Open **phpMyAdmin** → http://localhost/phpmyadmin
- Click **Import** → select `database/phishguard.sql` → **Go**

### 3️⃣ Configure backend

```bash
cd backend
copy config.example.php config.php
```

Edit `config.php` if your MySQL credentials differ (default: `root` / empty password).

### 4️⃣ Set admin password

Generate a fresh bcrypt hash:

```bash
php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
```

Then in phpMyAdmin → database `phishguard` → table `admins`:

```sql
UPDATE admins SET password = '<paste-hash-here>' WHERE username = 'admin';
```

### 5️⃣ Load the extension

1. Open `brave://extensions` (or `chrome://extensions`)
2. Enable **Developer mode** (top right)
3. Click **Load unpacked**
4. Select the `extension/` folder

### 6️⃣ Open the dashboard

| Field | Value |
|---|---|
| **URL** | http://localhost/phishguard/dashboard/ |
| **Username** | `admin` |
| **Password** | `admin123` |

---

## 🏗️ Architecture

```
┌─────────────────┐         ┌─────────────────┐
│   Chrome/Brave  │         │  Admin Browser  │
│    Extension    │         │                 │
└────────┬────────┘         └────────┬────────┘
         │                           │
         │ POST /scan.php            │ GET /dashboard.php
         │                           │
         ▼                           ▼
┌─────────────────────────────────────────────┐
│           PHP REST API (XAMPP)              │
│   scan.php · scans.php · stats.php · login  │
└────────────────────┬────────────────────────┘
                     │
                     │ PDO
                     ▼
        ┌─────────────────────────┐
        │    MySQL (phishguard)   │
        │  ┌──────┐  ┌─────────┐  │
        │  │admins│  │  scans  │  │
        │  └──────┘  └─────────┘  │
        └─────────────────────────┘
```

---

## 🔌 API Endpoints

| Method | Endpoint | Description |
|:------:|----------|-------------|
| `POST` | `/backend/scan.php` | Save a scan result |
| `GET`  | `/backend/scans.php` | List scans with filters |
| `GET`  | `/backend/stats.php` | Statistics by time range |
| `POST` | `/backend/login.php` | Admin authentication |

<details>
<summary><b>📥 Example: save a scan</b> (click to expand)</summary>

**Request:**
```bash
curl -X POST http://localhost/phishguard/backend/scan.php \
  -H "Content-Type: application/json" \
  -d '{
    "url": "http://192.168.1.1/login.htm",
    "score": 100,
    "level": "high",
    "reasons": ["No HTTPS", "Uses raw IP address", "Suspicious keyword in URL"]
  }'
```

**Response:**
```json
{
  "success": true,
  "id": 42
}
```
</details>

---

## 🧠 Detection Rules

| # | Rule | Weight | What it catches |
|:-:|------|:------:|-----------------|
| 1 | No HTTPS | +30 | Unencrypted connections |
| 2 | Uses raw IP address | +30 | `http://192.168.1.1/login` |
| 3 | Suspicious keyword in URL | +20 | `login`, `verify`, `secure`, `update` |
| 4 | Contains `@` in URL | +20 | `https://real.com@fake.com` |
| 5 | Punycode hostname | +25 | `xn--pypal-4ve.com` (fake PayPal) |
| 6 | Tunneling service | +40 | `trycloudflare.com`, `ngrok.io` |
| 7 | Too many subdomains | +15 | `secure.login.verify.paypal.xyz` |
| 8 | Auto-generated subdomain | +15 | `roughly-provinces-accounts.trycloudflare` |
| 9 | URL shortener | +15 | `bit.ly`, `tinyurl.com` |
| 10 | Very long URL (>75 chars) | +10 | Obfuscated URLs |
| 11 | HTML file in path | +10 | `.html` / `.php` in suspicious domains |

> **Score is capped at 100.** Whitelisted domains always return **0**.

---

## 🧪 Test URLs

| URL | Score | Level | Reason |
|-----|:-----:|:-----:|--------|
| `https://google.com` | 0 | 🟢 | Whitelisted |
| `https://tiktok.com/login` | 0 | 🟢 | Whitelisted |
| `http://neverssl.com` | 30 | 🟠 | No HTTPS |
| `http://192.168.1.1/login.htm` | 100 | 🔴 | IP + No HTTPS + keyword |
| `https://xxx.trycloudflare.com/login.html` | 85 | 🔴 | Tunnel + keyword + .html |

---

## 📂 Project Structure

```
phishguard/
├── 📁 backend/             PHP REST API
│   ├── config.example.php
│   ├── login.php
│   ├── scan.php
│   ├── scans.php
│   └── stats.php
├── 📁 dashboard/           Admin panel
│   ├── dashboard.php
│   ├── export.php
│   ├── index.php
│   ├── logout.php
│   └── style.css
├── 📁 database/
│   └── phishguard.sql
├── 📁 docs/
│   └── 📁 screenshots/
├── 📁 extension/           Chrome/Brave extension
│   ├── 📁 icons/
│   ├── analyzer.js
│   ├── background.js
│   ├── manifest.json
│   ├── popup.css
│   ├── popup.html
│   ├── popup.js
│   └── rules.js
├── .gitignore
├── LICENSE
└── README.md
```

---

## 🔒 Security

| Protection | Implementation |
|------------|----------------|
| **SQL Injection** | PDO prepared statements |
| **XSS** | `htmlspecialchars()` on output |
| **Password Storage** | `password_hash()` with bcrypt |
| **Input Validation** | `filter_var($url, FILTER_VALIDATE_URL)` |
| **CORS** | Restricted to `localhost` in dev |
| **Config Separation** | `config.php` excluded via `.gitignore` |

---

## 🛣️ Roadmap

- [x] **v1.0** — Rule-based detection + dashboard
- [ ] **v1.1** — Google Safe Browsing API integration
- [ ] **v1.2** — DOM content analysis
- [ ] **v1.3** — Machine learning classifier
- [ ] **v2.0** — Firefox port + mobile support

---

## 🧑‍💻 Technologies

<table>
<tr>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/javascript/F7DF1E" width="48" height="48" alt="JavaScript" />
<br><b>JavaScript</b>
</td>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/php/777BB4" width="48" height="48" alt="PHP" />
<br><b>PHP 8</b>
</td>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/mysql/4479A1" width="48" height="48" alt="MySQL" />
<br><b>MySQL</b>
</td>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/html5/E34F26" width="48" height="48" alt="HTML5" />
<br><b>HTML5</b>
</td>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/css3/1572B6" width="48" height="48" alt="CSS3" />
<br><b>CSS3</b>
</td>
<td align="center" width="96">
<img src="https://cdn.simpleicons.org/googlechrome/4285F4" width="48" height="48" alt="Chrome Extension" />
<br><b>Manifest V3</b>
</td>
</tr>
</table>

---

## ⚠️ Disclaimer

This tool is intended for **educational and authorized security testing only**. Phishing simulation tools used during development (such as Zphisher) run strictly in isolated lab environments. **Do not** use this project to conduct unauthorized attacks.

---

## 📄 License

This project is licensed under the **MIT License** — see [LICENSE](LICENSE) for details.

---

<div align="center">

### ⭐ If you find this project useful, please give it a star!

**Built with 🛡️ by [@aminearea](https://github.com/aminearea)**

<sub>3rd year Cyber Security Student · EMI · Morocco 🇲🇦</sub>

</div>
