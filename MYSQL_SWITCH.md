# Lucky Boss — SQLite to MySQL

**Short answer: nothing in the codebase blocks the switch.** It is a configuration
change plus a migration run, not a rewrite. I checked for the things that normally
make this painful and found none of them.

## What I checked, and what I found

| Risk | Result |
|---|---|
| Raw SQL in migrations (`DB::statement`, `PRAGMA`, `AUTOINCREMENT`) | **None.** All 23 migrations use the portable Schema builder. |
| Index names over MySQL's 64-character limit | **None.** Longest composite unique index is well under. |
| SQLite-specific column types | **None.** 13 `json()` columns, which Laravel maps to native JSON on MySQL. |
| A `mysql` connection block in `config/database.php` | **Present**, line 47. Already configured. |

The only reason the app runs on SQLite today is one line in `.env`.

## Current state

```
.env                     DB_CONNECTION=sqlite
config/database.php:20   'default' => env('DB_CONNECTION', 'sqlite')
database/database.sqlite 712 KB, seeded demo data
```

## The switch

**1. Create the database and user**

```sql
CREATE DATABASE luckyboss_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'luckyboss'@'localhost' IDENTIFIED BY 'a-strong-password-here';
GRANT ALL PRIVILEGES ON luckyboss_prod.* TO 'luckyboss'@'localhost';
FLUSH PRIVILEGES;
```

`utf8mb4` matters — the platform covers Singapore, Malaysia and India, so names and
job titles will contain characters outside Latin-1.

**2. Change `.env`**

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=luckyboss_prod
DB_USERNAME=luckyboss
DB_PASSWORD=a-strong-password-here
```

**3. Build the schema**

```bash
php artisan config:clear
php artisan migrate:fresh --seed
```

`migrate:fresh` drops everything first, so run it against the new empty MySQL
database — never against anything with real data in it.

**4. Confirm it is actually on MySQL**

```bash
php artisan tinker --execute="echo DB::connection()->getDriverName();"
```

Should print `mysql`. If it still prints `sqlite`, the config cache is stale — run
`php artisan config:clear` again.

## About the existing SQLite data

The 712 KB SQLite file holds seeded demo accounts, not real records. The clean move
is to start MySQL empty and re-seed, which is what step 3 does. Do **not** try to
copy the SQLite file across — the demo rows are exactly what a production database
should not contain.

If any of it does turn out to be worth keeping:

```bash
php artisan db:seed --class=DatabaseSeeder   # catalogs only, after migrate:fresh
```

## One thing to raise before going live

The `.env` currently has `APP_ENV=local` and `APP_DEBUG=true`. Debug mode on a
public server prints stack traces containing database credentials to any visitor
who triggers an error. Set `APP_DEBUG=false` in the same change as the database
switch, not later.

## What this does not fix

The database engine is not what is currently limiting the platform. The mobile apps
still cannot reach the backend for most things — `routes/api.php` exposes eight
endpoints, and there is no employer candidates endpoint, no applications endpoint,
and no resume-parse endpoint. Moving to MySQL is correct and worth doing, but it
does not make the apps work; the missing API routes are the thing that does.
