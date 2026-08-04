<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occurrences', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->nullable()->comment('company the event happened in, null when it is about the installation rather than one company');
            $table->string('type', 50)->comment('what happened, as entity.action, one of the cases of OccurrenceTypeEnum');
            $table->string('source', 50)->default('internal')->comment('where the event came from: the application itself, or a named integration');
            $table->string('subject_type')->nullable()->comment('class of the thing the event is about');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('id of the thing the event is about');
            $table->string('actor_type', 20)->comment('who caused it: a user, the system itself, or an integration');
            $table->unsignedBigInteger('actor_id')->nullable()->comment('id of the user who caused it, null when nobody did');
            $table->json('payload')->nullable()->comment('what the event carries, whose shape is a contract per type');
            $table->datetime('occurred_at')->comment('when it happened, which is not when it was written for an event coming from an integration');
            $table->timestamp('created_at')->nullable()->comment('when it was written');

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->index(['company_id', 'type']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};
