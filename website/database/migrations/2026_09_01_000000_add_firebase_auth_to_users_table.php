<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Firebase-issued identities on the users table.
 *
 * MySQL remains the system of record for every user, job, application and
 * image. Firebase is used only to prove identity: the app completes a Google
 * or phone sign-in, hands Laravel the resulting ID token, and Laravel creates
 * or finds the row here and issues its own Sanctum token. Nothing about a user
 * is stored in Firebase.
 *
 * Two existing columns have to relax for that to be possible:
 *
 * - `email` was NOT NULL. A candidate who signs in with a phone number has no
 *   email address at all, and inventing one ("+6591234567@phone.local") would
 *   put fake data in the column the employer portal displays.
 * - `password` was NOT NULL. There is no password in a Firebase sign-in, and
 *   writing a random hash would mean a "forgot password" flow could reset an
 *   account whose owner never had one.
 *
 * A NULL email keeps working with the existing unique index: MySQL, SQLite and
 * PostgreSQL all permit repeated NULLs in a unique column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // The Firebase `sub` claim. Unique because it is the join key —
            // a second row for the same Firebase user would split one person's
            // applications across two accounts.
            $table->string('firebase_uid', 128)->nullable()->unique()->after('id');

            // How this account signs in: password | google | phone.
            // Read before offering a password reset, so an account created with
            // Google is not told to check an inbox for a mail it will never get.
            $table->string('auth_provider', 20)->default('password')->after('password');

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['firebase_uid']);
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });
    }
};
