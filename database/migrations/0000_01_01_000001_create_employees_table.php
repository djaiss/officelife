<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the employee works for');
            $table->string('first_name')->comment('first name of the employee');
            $table->string('last_name')->comment('last name of the employee');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
