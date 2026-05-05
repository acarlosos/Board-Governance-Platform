<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('minute_versions', function (Blueprint $table): void {
            $table->index(['minute_id', 'version_number'], 'minute_versions_minute_version_idx');
        });
    }

    public function down(): void
    {
        Schema::table('minute_versions', function (Blueprint $table): void {
            $table->dropIndex('minute_versions_minute_version_idx');
        });
    }
};

