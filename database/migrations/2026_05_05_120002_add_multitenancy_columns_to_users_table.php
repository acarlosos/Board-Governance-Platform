<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('locale')->constrained()->nullOnDelete();
            $table->string('status', 32)->default('active')->after('tenant_id');
            $table->boolean('is_super_admin')->default(false)->after('status');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'status', 'is_super_admin']);
            $table->dropSoftDeletes();
        });
    }
};
