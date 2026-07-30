<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Table of the cesargb/laravel-magiclink package, which expects us to publish
 * its migrations rather than load them from the vendor directory. The four
 * migrations it ships are squashed here, since we are not in production yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('primary key');
            $table->string('token', 255)->comment('token of the link, which is what the url carries');
            $table->text('action')->comment('serialized action performed when the link is visited');
            $table->unsignedTinyInteger('num_visits')->default(0)->comment('how many times the link was visited');
            $table->unsignedTinyInteger('max_visits')->nullable()->comment('how many times the link may be visited');
            $table->string('access_code')->nullable()->comment('code the visitor must give on top of the link');
            $table->timestamp('available_at')->nullable()->comment('when the link expires');
            $table->timestamps();

            $table->index('available_at');
            $table->index(['max_visits', 'num_visits']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_links');
    }
};
