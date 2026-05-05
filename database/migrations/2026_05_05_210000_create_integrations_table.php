<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('type', 32);
            $table->string('provider', 32);
            $table->string('name');
            $table->string('status', 16)->default('inactive'); // inactive | active | error

            // encrypted:array -> texto criptografado (não usar JSON)
            $table->longText('config')->nullable();

            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 16)->nullable(); // success | failed
            $table->text('last_test_message')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'provider']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};

