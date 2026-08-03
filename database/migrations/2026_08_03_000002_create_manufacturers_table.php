<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the manufacturer belongs to, as the list is kept per company');
            $table->string('name')->comment('who makes the equipment, as opposed to who sold it');
            $table->string('website_url')->nullable()->comment('website of the manufacturer');
            $table->string('support_url')->nullable()->comment('where to ask the manufacturer for help');
            $table->string('support_email')->nullable()->comment('email address of the support desk');
            $table->string('support_phone')->nullable()->comment('phone number of the support desk');
            $table->text('notes')->nullable()->comment('anything worth writing down about dealing with them');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturers');
    }
};
