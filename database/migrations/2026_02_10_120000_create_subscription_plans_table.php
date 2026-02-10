<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('periodicity', ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly']);
            $table->unsignedInteger('consultations_per_period')->default(1);
            $table->unsignedInteger('price');
            $table->unsignedInteger('discount_percent')->default(0);
            $table->boolean('includes_home_visits')->default(true);
            $table->boolean('includes_teleconsultation')->default(false);
            $table->boolean('priority_booking')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
