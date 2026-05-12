<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_dashboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('period', 16);
            $table->json('payload');
            $table->boolean('is_stale')->default(false);
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'period']);
            $table->index(['is_stale', 'refreshed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_dashboard_snapshots');
    }
};
