<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('api_integrations', fn(Blueprint $table) => $table->text('encrypted_webhook_secret')->nullable()->after('encrypted_secret')); } public function down(): void { Schema::table('api_integrations', fn(Blueprint $table) => $table->dropColumn('encrypted_webhook_secret')); } };