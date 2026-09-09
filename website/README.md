# Lucky Boss — Web Portal

Laravel 12 recruitment portal for Singapore, Malaysia and India: a public job
board, a candidate portal with the Lucky AI agent, an employer ATS with the
hiring agent, and a super-admin back office.

**Running it needs PHP only.** There is no Node step — the CSS and JavaScript are
pre-compiled into `public/build/`, so `composer install` and `php artisan serve`
is the whole of it.

---

## Run it locally

You need **PHP 8.2+** and **Composer**. Nothing else.

```bash
git clone https://github.com/shantoshdurai/luckyboss-handover.git
cd luckyboss-handover
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8000
```

Then open **http://127.0.0.1:8000**.

`migrate:fresh --seed` builds the SQLite database from scratch and fills it with
demo data — vacancies, candidates, applications, the three subscription plans.
The database file itself is not in the repository, which is why this step is not
optional; run it again any time you want a clean slate.

### If PHP is missing an extension

Composer will say which. On Windows, uncomment the matching line in `php.ini`:

```ini
extension=pdo_sqlite
extension=sqlite3
extension=fileinfo
extension=mbstring
extension=openssl
```

---

## Sign in

The seeded accounts are below. Passwords are sent separately.

| Role | Email | Lands on |
|---|---|---|
| Super admin | `admin@luckyboss.test` | `/admin` |
| Employer | `employer@luckyboss.test` | `/employer` — Hiring AI |
| Candidate | `candidate@luckyboss.test` | `/job-seeker` — Lucky AI |

Sign in at **/login**. The "I am a" toggle is only a preference — it decides
where you land if an account holds both roles, and it never refuses a correct
password.

---

## Worth looking at

- **/** — the public board: trades, featured vacancies, search.
- **/register/employer** — four-step sign-up ending in a plan choice. Choosing a
  plan starts it immediately and charges nothing; there is no payment gateway
  yet, and the page says so.
- **/employer** — the hiring agent. It asks how you want to describe the role,
  then scores real candidates against it. It never invents a match score, never
  posts a vacancy you have not seen, and never contacts anybody on your behalf.
- **/job-seeker** — Lucky AI. It offers to read your CV or to ask you the
  questions, and refuses to score a profile too thin to score rather than
  guessing a number.
- **/admin/entitlements** — the switch that decides whether running out of
  credits actually stops an employer. It is **off**: usage is counted, nothing is
  refused.
- **/admin/job-matching** — the minimum match score, the bulk-apply cap, and the
  auto-apply kill switch.

---

## What is real and what is not

Deliberately, and worth knowing before testing:

- **No payment gateway.** Plans activate unpaid. Credits reach a company through
  `/admin/entitlements`.
- **No AI provider key.** Every row in `/admin/ai-apis` is disabled with no
  secret, so both agents run their scripted flows. They are complete flows, not
  placeholders — the matching, the shortlist and the scoring are all real.
- **Match scores are refused, not guessed.** A candidate who has typed nothing
  gets no score and no match list, and the page asks for what is missing instead.
- **Auto-apply only touches Lucky Boss vacancies.** It never applies on LinkedIn,
  Naukri or Monster, and it needs the candidate's own opt-in as well as the admin
  switch.

---

## Tests

```bash
php artisan test
```

301 feature tests covering registration, sign-in, matching, auto-apply, the
entitlement ledger, both agents and the admin screens.

---

## Layout

```
app/Http/Controllers/   Admin, Employer, Seeker and API controllers
app/Services/           JobMatchService, AutoApplyService, WorkTaxonomy,
                        HiringScript, AgentScript, entitlements
resources/views/        Blade templates; components/layouts/app.blade.php is the
                        one shell every signed-in screen uses
public/build/           Pre-compiled CSS and JS — do not expect a Node build
routes/web.php          Web routes
routes/api.php          Mobile app API (Sanctum)
```

The mobile apps live in a separate repository.
