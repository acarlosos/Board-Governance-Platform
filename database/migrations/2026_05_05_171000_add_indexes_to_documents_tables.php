<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->index(['document_id'], 'document_versions_document_idx');
        });

        Schema::table('document_access_logs', function (Blueprint $table): void {
            $table->index(['document_id'], 'document_access_logs_document_idx');
            $table->index(['user_id'], 'document_access_logs_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->dropIndex('document_versions_document_idx');
        });

        Schema::table('document_access_logs', function (Blueprint $table): void {
            $table->dropIndex('document_access_logs_document_idx');
            $table->dropIndex('document_access_logs_user_idx');
        });
    }
};

