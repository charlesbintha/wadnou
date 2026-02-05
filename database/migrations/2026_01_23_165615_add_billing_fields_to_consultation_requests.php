<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->decimal('distance_km', 8, 2)->nullable()->after('location_id');
            $table->integer('price_amount')->nullable()->after('distance_km'); // Prix en FCFA
            $table->string('payment_method')->nullable()->after('price_amount'); // orange_money, wave, cash
            $table->string('payment_status')->default('pending')->after('payment_method'); // pending, paid, failed
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn([
                'distance_km',
                'price_amount',
                'payment_method',
                'payment_status',
                'payment_reference',
                'paid_at',
            ]);
        });
    }
};
