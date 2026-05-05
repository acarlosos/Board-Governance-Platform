<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('minute_approvals', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('minute_id')->constrained('minutes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 32)->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('comments')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'minute_id', 'user_id']);
            $table->index(['tenant_id', 'minute_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minute_approvals');
    }
};

