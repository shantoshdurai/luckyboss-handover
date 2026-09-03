# 🚀 LUCKY BOSS — PRODUCTION HANDOVER & DEPLOYMENT GUIDE
**Platform Version:** 2.0 Production Ready  
**Target Markets:** Singapore (Primary), Malaysia, India  
**Date Updated:** August 2026  
**Document Purpose:** Complete technical transition and deployment manual for developers, DevOps engineers, and AI agents shifting Lucky Boss from local/demo state into live high-availability production.

---

## 📑 TABLE OF CONTENTS
1. [Architecture & Tech Stack Summary](#1-architecture--tech-stack-summary)
2. [Local Demo vs Production Mode Difference](#2-local-demo-vs-production-mode-difference)
3. [Production Server Requirements & Provisioning](#3-production-server-requirements--provisioning)
4. [Step-by-Step Production Deployment](#4-step-by-step-production-deployment)
5. [Database Architecture & Clean Seeding](#5-database-architecture--clean-seeding)
6. [AI Intelligence & Dual-Engine Operation](#6-ai-intelligence--dual-engine-operation)
7. [External Data Integration (LinkedIn, Naukri, Feeds)](#7-external-data-integration-linkedin-naukri-feeds)
8. [Flutter Mobile App Integration (API Endpoints)](#8-flutter-mobile-app-integration-api-endpoints)
9. [Cron Jobs, Queues & Supervisor Config](#9-cron-jobs-queues--supervisor-config)
10. [Admin Control Center Map & Credentials](#10-admin-control-center-map--credentials)

---

## 1. Architecture & Tech Stack Summary

| Layer | Technology / Framework | Details |
|---|---|---|
| **Backend** | Laravel 11.x on PHP 8.2+ | Strict types, modular controllers, Eloquent ORM, Sanctum API auth |
| **Frontend** | Tailwind CSS v4, Alpine.js, Blade Components | Vite 7.x bundler, custom micro-interactions, responsive mobile layout |
| **Primary AI Engine** | Google Gemini 2.5 Flash API | Multimodal PDF Document Vision (`inlineData`) + Live Chatbot Copilot |
| **Offline AI Engine** | Local Heuristic NLP Engine | Pure-PHP stream decompression (`FlateDecode`), Regex pattern matcher, 80+ skill taxonomy |
| **Audio Synthesizer** | Web Audio API (Tone generator) | Zero external MP3 dependencies, role-specific notification chime frequencies |
| **Database** | SQLite (Local/Demo) → MySQL 8.0+ / PostgreSQL (Production) | Normalized schema with foreign key constraints and transactional integrity |
| **Caching & Queues** | Redis / Database Queue | Async email delivery, webhook ingestion, AI background processing |

---

## 2. Local Demo vs Production Mode Difference

| Feature | Local Demo State (Current) | Live Production Target |
|---|---|---|
| **Database Engine** | `database.sqlite` | MySQL 8.0+ / MariaDB / AWS Aurora |
| **Sample Data** | Demo accounts (`candidate@...`, `employer@...`, `admin@...`) | Clean database with only Master Catalogs & real registered users |
| **App Debug** | `APP_DEBUG=true` | `APP_DEBUG=false` (Enforces custom error views) |
| **Domain & SSL** | `http://127.0.0.1:8000` | `https://your-domain.com` with Let's Encrypt / Cloudflare SSL |
| **AI Quotas** | Free Tier Gemini API key (20 req/day limit) | Production Paid API Key (unlimited queries / tier 1 billing) |
| **Mail System** | Log driver / Local simulation | SMTP (SendGrid, Mailgun, AWS SES, Postmark) |
| **Payments** | Sandbox / Simulated Stripe & HitPay | Live Stripe (`pk_live_...`, `sk_live_...`) / HitPay Singapore |

---

## 3. Production Server Requirements & Provisioning

### Recommended Server Specifications:
- **Cloud Provider:** DigitalOcean, AWS EC2, Hetzner Cloud, Linode, or Hostinger VPS
- **OS:** Ubuntu 22.04 LTS or 24.04 LTS
- **Hardware:** 2 vCPU, 4GB RAM, 50GB NVMe SSD (Minimum)
- **Web Server:** Nginx 1.24+ with HTTP/2 and Gzip/Brotli compression

### Quick Ubuntu Package Installation Command:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-curl \
php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl \
php8.2-redis redis-server git supervisor unzip certbot python3-certbot-nginx
```

---

## 4. Step-by-Step Production Deployment

### Step 1: Clone or Extract Codebase
```bash
cd /var/www
git clone https://github.com/shantoshdurai/luckyboss.git luckyboss-app
# OR unzip luckyboss-production.zip
cd /var/www/luckyboss-app
```

### Step 2: Configure Environment (`.env`)
Copy the production environment template:
```bash
cp .env.example .env
nano .env
```
Ensure the following variables are set:
```ini
APP_NAME="Lucky Boss"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://your-domain.com

# Production Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=luckyboss_prod
DB_USERNAME=luckyboss_admin
DB_PASSWORD=YourStrongDatabasePassword123!

# Cache & Session
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

# Production AI Engine
PLATFORM_AI_ENABLED=true
GEMINI_API_KEY=AIzaSyYourProductionGeminiKeyHere
GEMINI_MODEL=gemini-2.5-flash

# Transactional Mail Server
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@your-domain.com
MAIL_PASSWORD=YourSmtpPassword
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@your-domain.com"
MAIL_FROM_NAME="Lucky Boss"

# Payment Gateways (Singapore & International)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Step 3: Run Composer & Application Key Generation
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
```

### Step 4: Storage Linking & File Permissions
```bash
php artisan storage:link
sudo chown -R www-data:www-data /var/www/luckyboss-app
sudo chmod -R 775 /var/www/luckyboss-app/storage /var/www/luckyboss-app/bootstrap/cache
```

### Step 5: Compile Production Frontend Assets
```bash
npm install
npm run build
```

### Step 6: Optimize Laravel for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 5. Database Architecture & Clean Seeding

To launch with a clean database while preserving all 80+ Skill Catalogs, Job Categories, Industries, Countries, and creating a clean Super Admin:

```bash
# Wipe demo database and seed production catalogs:
php artisan migrate:fresh --seed
```

### Creating Your Live Super Admin Account via CLI:
```bash
php artisan tinker
```
```php
$user = App\Models\User::create([
    'name' => 'Master Super Admin',
    'email' => 'admin@your-domain.com',
    'password' => Hash::make('YourSuperSecurePassword2026!'),
    'phone' => '+65 8123 4567',
    'is_active' => true,
    'email_verified_at' => now(),
]);

$adminRole = App\Models\Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Administrator']);
$user->roles()->attach($adminRole->id);
```

---

## 6. AI Intelligence & Dual-Engine Operation

The platform features an enterprise **Dual-Engine AI Switch**:

```
                               ┌────────────────────────┐
                               │ User Uploads CV / Chat │
                               └───────────┬────────────┘
                                           │
                        ┌──────────────────┴──────────────────┐
                        ▼                                     ▼
           Is Cloud AI Enabled (Admin ON)?        Is Cloud AI Disabled (Admin OFF)?
           & API Quota Available?                 OR API Rate-Limited (429)?
                        │                                     │
                        ▼                                     ▼
           ┌────────────────────────┐            ┌────────────────────────┐
           │ Google Gemini Cloud AI │            │ Local Heuristic Engine │
           │ Multimodal PDF Vision  │            │ Pure-PHP Flate Parser  │
           │ Real-Time Generative   │            │ Zero-Cost Heuristic NLP│
           └────────────────────────┘            └────────────────────────┘
```

### Key Service Locations:
- **Core AI Engine:** `app/Services/AIRecruitmentEngineService.php`
- **Lucky AI Chatbot Controller:** `app/Http/Controllers/Api/AiChatController.php`
- **Admin Control Center:** `app/Http/Controllers/Admin/AiApiController.php`
- **Admin Switch:** Toggle `platform_ai_enabled` in **[Admin -> AI & API -> Feature Flags](http://127.0.0.1:8000/admin/ai-api?view=global-ai-settings)**.

---

## 7. External Data Integration (LinkedIn, Naukri, Feeds)

The platform is architected to ingest candidate batches from authorized external feeds and display them seamlessly to recruiters with source badges.

### Ingestion Endpoints (`/admin/external-data`):
1. **JSON Candidate Ingest Webhook:** `POST /api/v1/external-data/candidates/sync`
2. **XML / RSS Job Feed Ingest:** `POST /api/v1/external-data/jobs/feed`

### Normalized Data Format:
```json
{
  "source_name": "LinkedIn Recruiter",
  "source_url": "https://linkedin.com/in/candidate",
  "name": "Santosh P",
  "email": "santoshp123steam@gmail.com",
  "phone": "+91-6383515761",
  "title": "Mobile App Developer (Flutter)",
  "skills": ["Flutter", "Python", "React", "Firebase", "WebSockets"],
  "location": "Singapore / Remote",
  "years_experience": 2
}
```

---

## 8. Flutter Mobile App Integration (API Endpoints)

The Laravel backend contains all required REST API routes ready for Flutter (iOS & Android) consumption:

| HTTP Method | API Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/v1/auth/login` | Mobile Sanctum Bearer Token Auth |
| `POST` | `/api/v1/auth/register` | Candidate & Employer Mobile Registration |
| `GET` | `/api/v1/jobs` | Job Feed with Country & Category Filters |
| `POST` | `/api/v1/jobs/{id}/apply` | 1-Click Mobile Job Application |
| `POST` | `/api/v1/resume/upload` | Mobile Camera / File PDF CV Upload & Vision Parse |
| `POST` | `/api/v1/ai/chat` | Mobile In-App Lucky AI Career Copilot |
| `GET` | `/api/v1/notifications` | Real-time Push Notification Alerts |

---

## 9. Cron Jobs, Queues & Supervisor Config

### Crontab Setup:
Add the Laravel schedule runner to Ubuntu crontab:
```bash
sudo crontab -e -u www-data
```
Add line:
```cron
* * * * * cd /var/www/luckyboss-app && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisor Queue Worker Config (`/etc/supervisor/conf.d/luckyboss-worker.conf`):
```ini
[program:luckyboss-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/luckyboss-app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/luckyboss-app/storage/logs/worker.log
stopwaitsecs=3600
```
Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start luckyboss-worker:*
```

---

## 10. Admin Control Center Map & Credentials

### Default Portals:
- **Public Portal:** `https://your-domain.com`
- **Candidate Portal:** `https://your-domain.com/job-seeker/dashboard`
- **Employer Portal:** `https://your-domain.com/employer/dashboard`
- **Super Admin Center:** `https://your-domain.com/admin`

### Key Admin Tool Shortcuts:
- **AI & API Control Center:** `/admin/ai-api`
- **Live API Telemetry Hub:** `/admin/ai-api?view=api-usage`
- **Candidate Operations:** `/admin/candidates`
- **Employer Verification:** `/admin/companies`
- **Job Master Taxonomies:** `/admin/masters`
- **Revenue & Transactions:** `/admin/payments`
- **Notification Logs:** `/admin/notifications`
- **Mobile App Roadmap:** `/admin/command/mobile-apps`

---

*Authored for the Lucky Boss Engineering & AI Deployment Team.*