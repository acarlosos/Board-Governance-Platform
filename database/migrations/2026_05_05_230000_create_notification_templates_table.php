<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();

            $table->string('key', 128);
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->string('locale', 16)->default('pt_BR');
            $table->string('channel', 16)->default('database'); // database | email
            $table->string('status', 16)->default('active'); // active | inactive
            $table->json('variables')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'key', 'locale', 'channel']);
            $table->index(['key', 'locale', 'channel']); // para fallback global
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

