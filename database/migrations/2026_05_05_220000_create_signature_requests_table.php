<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('signable_type');
            $table->unsignedBigInteger('signable_id');

            $table->string('provider', 32)->default('internal'); // internal | docusign
            $table->foreignId('integration_id')->nullable()->constrained('integrations')->nullOnDelete();

            $table->string('title');
            $table->text('message')->nullable();

            $table->string('status', 16)->default('draft'); // draft | sent | completed | cancelled | failed

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('external_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'provider']);
            $table->index(['signable_type', 'signable_id']);
            $table->index(['tenant_id', 'integration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};

