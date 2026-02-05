<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->timestamp('sla_warning_sent_at')->nullable()->after('sla_due_at');
            $table->timestamp('sla_breach_sent_at')->nullable()->after('sla_warning_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn(['sla_warning_sent_at', 'sla_breach_sent_at']);
        });
    }
};
