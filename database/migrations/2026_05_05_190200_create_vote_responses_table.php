<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vote_responses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();
            $table->foreignId('vote_option_id')->nullable()->constrained('vote_options')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->text('comment')->nullable();
            $table->timestamp('voted_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'vote_id', 'user_id']);
            $table->index(['tenant_id', 'vote_id']);
            $table->index(['tenant_id', 'vote_option_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_responses');
    }
};

