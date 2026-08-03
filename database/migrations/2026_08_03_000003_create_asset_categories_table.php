<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the category belongs to');
            $table->string('name')->comment('name of the category, as it reads on the screen');
            $table->string('type', 20)->comment('which family of equipment it groups, one of the cases of AssetCategoryTypeEnum');
            $table->boolean('requires_acceptance')->default(false)->comment('whether somebody handed one has to accept the terms first');
            $table->text('eula_text')->nullable()->comment('the terms they accept');
            $table->boolean('send_checkout_email')->default(false)->comment('whether handing one out emails the person who receives it');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
