<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vote_options', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('order_column')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'vote_id']);
            $table->index(['tenant_id', 'order_column']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vote_options');
    }
};

