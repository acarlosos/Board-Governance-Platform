<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table): void {
            $table->index(['tenant_id', 'status', 'last_activity_at']);
            $table->index(['user_id', 'status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::table('auth_sessions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'status', 'last_activity_at']);
            $table->dropIndex(['user_id', 'status', 'last_activity_at']);
        });
    }
};

