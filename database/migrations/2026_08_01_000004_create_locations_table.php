<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the office belongs to');
            $table->string('name')->comment('name of the office, as it reads on the screen');
            $table->string('country', 2)->nullable()->comment('country the office is in, as an iso 3166-1 alpha-2 code');
            $table->string('city')->nullable()->comment('city the office is in');
            $table->text('address')->nullable()->comment('street address of the office');
            $table->string('timezone', 50)->nullable()->comment('timezone the office keeps, falls back to the company timezone');
            $table->timestamp('archived_at')->nullable()->comment('day the office was closed, null while it is still open');
            $table->boolean('is_primary')->default(false)->comment('whether the office is the head office of the company');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
