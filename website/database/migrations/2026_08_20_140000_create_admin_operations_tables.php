<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('module'); $table->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete(); $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('admin_records', function (Blueprint $table) {
            $table->id(); $table->string('module')->index(); $table->string('name'); $table->string('slug')->index(); $table->text('description')->nullable(); $table->json('payload')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['module','slug']);
        });
        Schema::create('external_sources', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('source_type'); $table->string('feed_type')->default('manual'); $table->string('status')->default('active'); $table->boolean('contacts_visible')->default(false); $table->unsignedInteger('import_limit')->nullable(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id(); $table->foreignId('external_source_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('data_type'); $table->string('status')->default('queued'); $table->unsignedInteger('records_received')->default(0); $table->unsignedInteger('records_imported')->default(0); $table->unsignedInteger('records_failed')->default(0); $table->text('error_log')->nullable(); $table->timestamps();
        });
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); $table->string('source')->default('website'); $table->string('subject'); $table->text('message'); $table->string('status')->default('new')->index(); $table->string('priority')->default('normal'); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('number')->unique(); $table->string('type')->default('employer'); $table->string('status')->default('issued'); $table->string('currency_code',3)->default('SGD'); $table->decimal('amount',12,2); $table->decimal('tax_amount',12,2)->default(0); $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('invoices'); Schema::dropIfExists('support_tickets'); Schema::dropIfExists('import_batches'); Schema::dropIfExists('external_sources'); Schema::dropIfExists('admin_records'); Schema::dropIfExists('permission_role'); Schema::dropIfExists('permissions');
    }
};