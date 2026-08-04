<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('asset_id')->comment('equipment being handed over');
            $table->string('assignee_type', 20)->comment('what kind of thing is holding it, one of the cases of AssetAssigneeTypeEnum');
            $table->unsignedBigInteger('assignee_id')->comment('who or what is holding it');
            $table->unsignedBigInteger('assigned_by_user_id')->nullable()->comment('who handed it over');
            $table->datetime('assigned_at')->comment('when it was handed over');
            $table->date('expected_return_at')->nullable()->comment('when it is due back, null when it is not a loan');
            $table->datetime('returned_at')->nullable()->comment('when it came back, null while somebody still has it');
            $table->unsignedBigInteger('returned_to_location_id')->nullable()->comment('office it came back to');
            $table->text('checkout_notes')->nullable()->comment('anything said when it was handed over');
            $table->text('checkin_notes')->nullable()->comment('anything said when it came back');
            $table->string('condition_at_checkout', 20)->nullable()->comment('what state it was in when handed over, one of the cases of AssetConditionEnum');
            $table->string('condition_at_checkin', 20)->nullable()->comment('what state it was in when it came back, one of the cases of AssetConditionEnum');
            $table->datetime('overdue_notified_at')->nullable()->comment('when it was flagged as late, so it is flagged once rather than every day');
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $table->foreign('assigned_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('returned_to_location_id')->references('id')->on('locations')->nullOnDelete();
            $table->index(['assignee_type', 'assignee_id']);
            $table->index(['asset_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
