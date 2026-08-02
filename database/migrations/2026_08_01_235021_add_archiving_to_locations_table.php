<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('timezone')->comment('day the office was closed, null while it is still open');
            $table->boolean('is_primary')->default(false)->after('archived_at')->comment('whether the office is the head office of the company');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn(['archived_at', 'is_primary']);
        });
    }
};
