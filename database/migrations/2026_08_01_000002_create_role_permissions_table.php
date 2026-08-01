<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('role_id')->comment('role the permission is granted to');
            $table->string('permission', 50)->comment('permission the role grants, one of the cases of PermissionEnum');
            $table->string('scope', 20)->comment('which employees the permission covers, one of the cases of ScopeEnum');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unique(['role_id', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
