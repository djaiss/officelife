<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company that owns the equipment');
            $table->unsignedBigInteger('asset_model_id')->comment('the model this one is an example of');
            $table->unsignedBigInteger('status_id')->comment('what state it is in, which is not who is holding it');
            $table->string('asset_tag')->comment('identifier the company writes on the label and controls, such as OL-LAPTOP-0042');
            $table->string('serial_number')->nullable()->comment('identifier the manufacturer stamped on it, which nobody here controls and which is not unique');
            $table->string('name')->nullable()->comment('what people call this particular one');
            $table->unsignedBigInteger('default_location_id')->nullable()->comment('office it belongs to when nobody has it');
            $table->unsignedBigInteger('current_location_id')->nullable()->comment('office it is in now');
            $table->date('purchase_date')->nullable()->comment('day it was bought');
            $table->unsignedBigInteger('purchase_cost')->nullable()->comment('what it cost, in the minor units of the currency of the company');
            $table->string('order_number')->nullable()->comment('reference of the order it came in on');
            $table->date('warranty_expires_at')->nullable()->comment('day the warranty runs out');
            $table->date('end_of_life_at')->nullable()->comment('day it is expected to stop being worth keeping');
            $table->boolean('is_byod')->default(false)->comment('whether the employee owns it rather than the company');
            $table->boolean('is_requestable')->default(false)->comment('whether an employee may ask for this particular one');
            $table->text('notes')->nullable()->comment('anything worth writing down about this one');
            $table->timestamp('archived_at')->nullable()->comment('day it left the fleet, null while it is still in it');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('asset_model_id')->references('id')->on('asset_models')->restrictOnDelete();
            $table->foreign('status_id')->references('id')->on('asset_statuses')->restrictOnDelete();
            $table->foreign('default_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('current_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->unique(['company_id', 'asset_tag']);
            $table->index(['company_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
