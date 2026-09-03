<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id(); $table->string('code', 3)->unique(); $table->string('name'); $table->string('symbol', 8); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('packages', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_type_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('company_grade_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->unsignedInteger('validity_days')->default(30); $table->json('entitlements')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('package_prices', function (Blueprint $table) {
            $table->id(); $table->foreignId('package_id')->constrained()->cascadeOnDelete(); $table->string('currency_code', 3); $table->decimal('amount', 12, 2); $table->decimal('tax_rate', 5, 2)->default(0); $table->timestamps(); $table->unique(['package_id', 'currency_code']);
        });
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('package_id')->constrained()->restrictOnDelete(); $table->string('status')->default('pending')->index(); $table->date('starts_at')->nullable(); $table->date('expires_at')->nullable()->index(); $table->json('entitlements')->nullable(); $table->string('currency_code', 3)->default('SGD'); $table->decimal('amount', 12, 2)->default(0); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete(); $table->string('reference')->unique(); $table->string('purpose'); $table->string('gateway')->default('manual'); $table->string('status')->default('pending')->index(); $table->string('currency_code', 3)->default('SGD'); $table->decimal('amount', 12, 2); $table->json('gateway_payload')->nullable(); $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
        Schema::create('interviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('job_application_id')->constrained()->cascadeOnDelete(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('interviewer_id')->nullable()->constrained('users')->nullOnDelete(); $table->string('mode')->default('in-person'); $table->timestamp('scheduled_at'); $table->unsignedSmallInteger('duration_minutes')->default(45); $table->string('time_zone')->default('Asia/Singapore'); $table->string('venue')->nullable(); $table->string('meeting_link')->nullable(); $table->text('notes')->nullable(); $table->string('status')->default('scheduled')->index(); $table->timestamps();
        });
        Schema::create('offers', function (Blueprint $table) {
            $table->id(); $table->foreignId('job_application_id')->constrained()->cascadeOnDelete(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->string('position'); $table->decimal('salary', 12, 2); $table->string('currency_code', 3)->default('SGD'); $table->date('joining_date')->nullable(); $table->string('work_location')->nullable(); $table->text('terms')->nullable(); $table->string('status')->default('draft')->index(); $table->timestamp('sent_at')->nullable(); $table->timestamp('responded_at')->nullable(); $table->timestamps();
        });
        Schema::create('candidate_notes', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('job_application_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('note'); $table->timestamps();
        });
        Schema::create('sliders', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('subtitle')->nullable(); $table->string('image_path')->nullable(); $table->string('cta_text')->nullable(); $table->string('cta_url')->nullable(); $table->unsignedSmallInteger('sort_order')->default(0); $table->date('starts_at')->nullable(); $table->date('ends_at')->nullable(); $table->boolean('web_enabled')->default(true); $table->boolean('app_enabled')->default(true); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('api_integrations', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->string('name'); $table->string('provider')->nullable(); $table->text('encrypted_secret')->nullable(); $table->string('environment')->default('sandbox'); $table->boolean('is_enabled')->default(false); $table->unsignedBigInteger('monthly_limit')->nullable(); $table->unsignedBigInteger('usage_count')->default(0); $table->timestamp('last_requested_at')->nullable(); $table->text('last_error')->nullable(); $table->timestamps();
        });
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('type'); $table->string('title'); $table->text('body'); $table->json('data')->nullable(); $table->string('sound')->nullable(); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::table('job_applications', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('candidate_id')->constrained('users')->nullOnDelete(); $table->timestamp('last_activity_at')->nullable()->after('applied_at');
        });
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('job_application_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('from_status')->nullable(); $table->string('to_status'); $table->text('remark')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories'); Schema::table('job_applications', function (Blueprint $table) { $table->dropConstrainedForeignId('assigned_to'); $table->dropColumn('last_activity_at'); }); Schema::dropIfExists('platform_notifications'); Schema::dropIfExists('api_integrations'); Schema::dropIfExists('sliders'); Schema::dropIfExists('candidate_notes'); Schema::dropIfExists('offers'); Schema::dropIfExists('interviews'); Schema::dropIfExists('payments'); Schema::dropIfExists('subscriptions'); Schema::dropIfExists('package_prices'); Schema::dropIfExists('packages'); Schema::dropIfExists('currencies');
    }
};