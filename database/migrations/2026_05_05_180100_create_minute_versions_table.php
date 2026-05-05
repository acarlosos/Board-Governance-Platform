<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('minute_versions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('minute_id')->constrained('minutes')->cascadeOnDelete();

            $table->unsignedInteger('version_number');
            $table->longText('content');
            $table->string('changes_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'minute_id', 'version_number']);
            $table->index(['tenant_id', 'minute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minute_versions');
    }
};

