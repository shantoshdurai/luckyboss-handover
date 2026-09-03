<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->boolean('is_sponsored')->default(false)->after('is_urgent');
            $table->boolean('is_external')->default(false)->after('is_sponsored');
            $table->timestamp('archived_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropColumn(['is_sponsored', 'is_external', 'archived_at']);
        });
    }
};
