# 🌟 Lucky Boss — Master Handover & Deployment Package

> **Platform**: Lucky Boss (Global Recruitment, ATS Pipeline & AI Career Platform)  
> **Markets**: Singapore (SG), Malaysia (MY), India (IN)  
> **Brand Identity**: Primary Navy (`#031F49`), Emerald Green (`#18A66A` / `#10B981`), Accent Blue (`#2563EB`)  
> **Architecture**: Unified Laravel 12 Backend API (MySQL) + Flutter Mobile Apps (Candidate & Employer)  

---

## 📂 Handover Package Directory Structure

```text
LuckyBoss_Handover/
├── 🌐 website/               # Complete Laravel 12 Web Portal & Unified Mobile API
│   ├── app/                 # Controllers, Services, Models, API Endpoints
│   ├── database/            # 29 Migrations, Seeders, SQLite Dev DB
│   ├── public/              # Pre-compiled assets (Zero Node.js runtime required)
│   ├── resources/views/     # Blade templates (Admin, ATS, Portal, Landing pages)
│   ├── routes/              # web.php, api.php (44 total verified endpoints)
│   └── vendor/              # Pre-installed Composer packages
│
├── 📱 apks/                  # Production-Ready Android Release APKs (arm64-v8a)
│   ├── Luckyboss_JobSeeker.apk       # Candidate App with Phone Auth, Matching & AI
│   └── Luckyboss_EmployerPortal.apk  # Employer ATS, Vacancy Wizard & Pipeline
│
├── 🗄️ database/              # Production Database Assets
│   └── luckyboss_mysql.sql  # Complete pre-seeded MySQL production schema & data
│
├── 📖 DEPLOY.md              # Step-by-step server deployment guide for luckyboss.org
├── 📖 MYSQL_SWITCH.md        # Database configuration and migration guide
└── 📄 README.md              # This master documentation file
```

---

## 🚀 Quick Start & Component Summary

### 1. 🌐 Web Portal & Backend API (`website/`)
* **Framework**: Laravel 12 (PHP 8.2+)
* **Zero-Build Assets**: All CSS/JS assets are pre-compiled and bundled into `public/build/` and `public/css/app.css` — **no Node.js build step needed on the server**.
* **Unified Endpoints (44 Routes)**:
  * **Authentication**: `POST /api/v1/auth/login`, `POST /api/v1/auth/firebase` (Phone OTP), `POST /api/v1/auth/demo`
  * **Job Seeker**: Profile management, skills taxonomy, search, photo upload, resume upload
  * **Employer Portal**: Vacancy publishing, ATS candidate stages, company logos, pipeline telemetry
  * **Telemetry & Insights**: Real-time impressions, vacancy boost analytics (`GET /api/v1/employer/insights`)
  * **AI Copilot**: Dual-engine integration (Gemini + local heuristic fallbacks)

### 2. 📱 Mobile Applications (`apks/`)
* **Framework**: Flutter 3.44.0 (Dart 3.x)
* **Pre-built APKs**:
  * **`Luckyboss_JobSeeker.apk` (22.5 MB)**:
    * Firebase Phone OTP Authentication (SMS sign-in)
    * Real-time high-accuracy job recommendations (98% match cap)
    * Interactive application confirmation and celebration flow
    * Single-question recommendation cadence
    * Lucky AI Career Copilot assistant
  * **`Luckyboss_EmployerPortal.apk` (21.1 MB)**:
    * Corporate employer sign-in & team management
    * Full 12-field vacancy creation wizard (no artificial pre-fills)
    * Candidate pipeline review (Shortlisted, Interviewing, Offered, Hired)
    * Real-time vacancy analytics and impressions

### 3. 🗄️ Database (`database/` & `MYSQL_SWITCH.md`)
* Pre-seeded MySQL dump ready for direct import: `database/luckyboss_mysql.sql`.
* 29 portable migrations handling zero-downtime schema evolution.

---

## 🛠️ Production Server Deployment (luckyboss.org)

To deploy this package to the live production server:

1. **Back up existing database**:
   ```bash
   mysqldump -u luckyboss -p luckyboss_prod > backup_$(date +%F).sql
   ```
2. **Upload `website/` to server web root** (e.g. `/var/www/luckyboss`), keeping the existing production `.env`.
3. **Run migrations**:
   ```bash
   php artisan migrate --force
   ```
4. **Clear application caches**:
   ```bash
   php artisan config:clear && php artisan route:clear && php artisan view:clear
   ```
5. **Verify deployment**:
   ```bash
   curl -I https://luckyboss.org/api/v1/skills/suggested
   # Expect HTTP 200 OK
   ```

*Detailed deployment instructions are documented in [DEPLOY.md](DEPLOY.md).*

---

## 🔑 Demo & Test Accounts

| Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@luckyboss.test` | `password` |
| **Employer** | `employer@luckyboss.test` | `password` |
| **Candidate** | `candidate@luckyboss.test` | `password` |

---

## 📦 GitHub Repositories

* **Master Handover Package**: [https://github.com/shantoshdurai/luckyboss-handover](https://github.com/shantoshdurai/luckyboss-handover)
* **Web Portal & Backend Repository**: [https://github.com/shantoshdurai/luckyboss](https://github.com/shantoshdurai/luckyboss)
* **Mobile Applications Source Repository**: [https://github.com/shantoshdurai/luckyboss-mobile-apps](https://github.com/shantoshdurai/luckyboss-mobile-apps)
