# Lucky Boss — handover package

Everything in one place: the Laravel web portal, both Flutter apps, the built
Android APKs, and the deployment notes.

| Folder | What it is |
|---|---|
| **`website/`** | The Laravel 12 web portal — public job board, candidate portal, employer ATS, super admin. **Start here.** |
| `apps/` | Flutter source for the Job Seeker and Employer Portal apps |
| `apks/` | Built Android APKs, ready to install on a phone |
| `database/` | SQL dump for a MySQL deployment |
| `DEPLOY.md` | Server deployment notes |
| `MYSQL_SWITCH.md` | Moving from SQLite to MySQL |

---

## Run the website locally

You need **PHP 8.2 or newer** and **Composer**. Nothing else — no Node, no npm.
The CSS and JavaScript are already compiled.

```bash
git clone https://github.com/shantoshdurai/luckyboss-handover.git
cd luckyboss-handover/website
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8000
```

Then open **http://127.0.0.1:8000**.

That `migrate:fresh --seed` step builds the database and fills it with demo data
— vacancies, candidates, applications, the three subscription plans. Run it
again any time you want to start clean.

**Don't have PHP?** On Windows the quickest route is
[Laragon](https://laragon.org/download/) or
[XAMPP](https://www.apachefriends.org/), both of which include PHP and Composer.
On a Mac, `brew install php composer`.

If Composer complains that an extension is missing, uncomment the matching line
in `php.ini` — the usual ones are `pdo_sqlite`, `sqlite3`, `fileinfo`,
`mbstring` and `openssl`.

---

## Sign in

Three accounts are seeded. **Passwords are sent separately.**

| Role | Email | Where it lands |
|---|---|---|
| Super admin | `admin@luckyboss.test` | `/admin` |
| Employer | `employer@luckyboss.test` | `/employer` — the hiring agent |
| Candidate | `candidate@luckyboss.test` | `/job-seeker` — Lucky AI |

Sign in at **/login** for all three.

### Opening the admin

1. Go to **http://127.0.0.1:8000/login**
2. Enter `admin@luckyboss.test` and the admin password
3. **Ignore the "Job seeker / Employer" buttons** — they do not apply to the
   admin, so leave them as they are
4. Press **Sign in**

You land on the Admin Dashboard. Everything is in the menu down the left-hand
side: Employers, Candidates, Job Listings, ATS Pipeline, Subscriptions & Pay,
AI & APIs, Masters & Feeds, CMS & Blog, and Settings & Branding. Each one expands.

There is also a dedicated **/admin/login** with no Job seeker / Employer buttons
on it — same form, same result, if you would rather go straight there.

**If you cannot see the left-hand menu**, the browser window is narrower than
1024px. The menu folds behind the **☰** at the top-left, next to "Admin
Dashboard"; maximising the window brings the column back.

**If "Sign in" appears to do nothing**, it is the rate limit — five attempts a
minute. Wait a minute and try again.

---

## Worth a look

- **`/`** — the public board.
- **`/register/employer`** — sign-up in four steps, ending on a plan choice.
- **`/employer`** — the hiring agent: describe a role, it scores real candidates
  against it.
- **`/job-seeker`** — Lucky AI: it offers to read your CV or ask you the
  questions.
- **`/admin`** — the back office, including the switches for match scoring,
  auto-apply and credits.

`website/README.md` has the fuller tour, including what is deliberately not
wired up yet.

---

## Try the apps

Copy an APK from `apks/` to an Android phone and install it. Both apps point at
`http://127.0.0.1:8000` by default, so to use them against a machine running the
site, rebuild with the host address:

```bash
flutter build apk --release --dart-define=API_BASE_URL=http://<your-ip>:8000
```
