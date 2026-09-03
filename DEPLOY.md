# Luckyboss — Deploying the merged backend to luckyboss.org

## What this package is

One backend that serves the website, the admin panel, and both mobile apps from
the same MySQL database. It is a **superset** of what is currently live: nothing
that runs on luckyboss.org today is removed.

```
website + admin  ─┐
job seeker app   ─┼──►  Laravel  ──►  MySQL
employer app     ─┘
```

Firebase is not part of that picture. It verifies a phone number and nothing
else; Laravel then creates the user in MySQL, which stays the system of record.

## Why the live site must be updated before the apps will work

The APKs in `apks/` are built against `https://luckyboss.org`. The build
currently deployed there is from 27 August and does **not** have the endpoints
the apps now call. Until this backend is deployed, the apps will fail at
sign-in and onboarding.

Missing on the live build today:

| Endpoint | Used for |
|---|---|
| `POST /api/v1/auth/firebase` | phone OTP sign-in |
| `GET`/`PUT /api/v1/job-seeker/profile` | saving and reloading the profile |
| `GET /api/v1/skills/search` | onboarding skill search |
| `GET /api/v1/skills/suggested` | onboarding starting list |
| `POST /api/v1/skills/related` | related-skill suggestions |
| `POST /api/v1/resume/parse` | resume autofill |
| `POST`/`DELETE /api/v1/job-seeker/photo` | profile photo |
| `POST /api/v1/auth/demo` | read-only demo sign-in |
| `GET /api/v1/employer/candidates` | employer pipeline |
| `PUT /api/v1/employer/candidates/{id}/status` | moving a candidate |
| `GET /api/v1/employer/jobs/{job}/candidates` | per-vacancy pipeline |
| `GET /api/v1/employer/insights` | employer analytics overview & apply rates |
| `GET /api/v1/employer/jobs/{job}/insights` | per-vacancy impression & apply telemetry |
| `GET /api/v1/app-settings` | runtime platform & feature configuration |
| `POST /api/v1/employer/ai/job-description` | AI vacancy writer |
| `POST /api/v1/employer/ai/interview-questions` | AI candidate interview question generator |
| `POST /api/v1/employer/ai/letter` | AI recruitment letter & offer generator |

## What was kept from the live build

The live deployment had three things this codebase never grew. They were merged
in rather than overwritten:

- `GET  /api/v1/employer/jobs` — an employer listing their own vacancies
- `POST /api/v1/employer/jobs/{job}` — **editing a vacancy after posting**
- `POST /api/v1/employer/company/logo` — company logo upload

`POST /api/v1/job-seeker/profile/photo` is also kept as a legacy alias, so
anything already calling the live path keeps working.

## Deployment steps

1. **Back up the live database first.** This is not optional — step 3 alters the
   `users` and profile tables.

2. Upload the Laravel application, keeping the server's existing `.env`.
   Do not overwrite `.env` — it holds the production keys.

3. Run the migrations:

   ```bash
   php artisan migrate --force
   ```

   The new migrations:
   - Add `firebase_uid` and `auth_provider` to `users`, and make `email` and
     `password` nullable for candidate phone OTP.
   - Add `resume_path` to `candidate_profiles` so uploaded resumes are persisted safely.
   - Create `job_views` and `job_boosts` tables for real-time impression telemetry.
   - Create `ai_usage_log` table to enforce plan limits and meter AI usage per spec.

4. Clear the caches:

   ```bash
   php artisan config:clear && php artisan route:clear && php artisan view:clear
   ```

5. Confirm `APP_DEBUG=false` in `.env`. With it on, API errors return exception
   classes and full filesystem paths to the caller.

6. Check the new endpoints answer:

   ```bash
   curl -o /dev/null -w "%{http_code}\n" https://luckyboss.org/api/v1/skills/suggested
   ```

   200 means the deployment worked. 404 means the old build is still being
   served — clear the route cache.

### If the live database already holds real users

Run the migrations (step 3) **on top of the existing database**. Do not import
`database/luckyboss_mysql.sql` — that file is a full dump from the development
database and would replace the live data.

`luckyboss_mysql.sql` is only for a fresh install with no real users yet.

## Firebase — the one outstanding item

Phone OTP is finished in code and verified end to end, but Firebase itself will
not send an SMS until one of these is done in the console
(project `luckyboss-617d2`):

- add a test phone number under **Authentication → Sign-in method → Phone →
  Phone numbers for testing** (free, no SMS is actually sent), **or**
- enable Blaze billing, for real SMS to real candidates.

Already done: the Phone provider is enabled, the SHA-1 fingerprint is
registered, and the SMS region policy allows India. The current API response is
`BILLING_NOT_ENABLED`, which is the billing/test-number gate and nothing else.

## The APKs

`apks/` holds arm64 release builds, which covers current Android phones.
The API address is compiled in at build time, so pointing the apps somewhere
else means rebuilding:

```bash
flutter build apk --release --split-per-abi --dart-define=API_BASE_URL=https://luckyboss.org
```

Both are signed with the Android debug key. That is fine for direct install but
must be replaced with a proper release keystore before any Play Store upload —
and the new key's SHA-1 has to be added to Firebase, or phone sign-in stops
working in the store build.
