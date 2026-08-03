<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_models', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the model belongs to');
            $table->unsignedBigInteger('manufacturer_id')->comment('who makes it');
            $table->unsignedBigInteger('asset_category_id')->comment('which family it belongs to');
            $table->string('name')->comment('what the model is called, such as Apple MacBook Pro 14-inch M4 Pro');
            $table->string('model_number')->nullable()->comment('reference the manufacturer gives the model');
            $table->string('image_path')->nullable()->comment('path of a picture of the model');
            $table->unsignedInteger('useful_life_months')->nullable()->comment('how long one of these is expected to last');
            $table->boolean('is_requestable')->default(false)->comment('whether an employee may ask for one');
            $table->text('notes')->nullable()->comment('anything worth writing down about the model');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->restrictOnDelete();
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_models');
    }
};
