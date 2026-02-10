<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active');
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->unsignedInteger('consultations_used')->default(0);
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_subscriptions');
    }
};
