<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compatibilidade com bases já migradas com is_platform_admin (Fase 1 inicial).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_platform_admin') && ! Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_platform_admin', 'is_super_admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_super_admin') && ! Schema::hasColumn('users', 'is_platform_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_super_admin', 'is_platform_admin');
            });
        }
    }
};
