<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks an account as the public read-only demo.
 *
 * The flag lives on the row rather than in config so the read-only rule is a
 * property of the account itself. A demo login is a real login — real Sanctum
 * token, real seeded data — and this column is what the API middleware checks
 * before allowing any write. Without it, "read-only" would only ever be a
 * disabled button in Flutter, which anyone can bypass by calling the endpoint
 * directly (spec section 93: do not only hide the button in Flutter).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
    }
};
