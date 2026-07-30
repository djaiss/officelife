<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the user belongs to');
            $table->string('email')->unique()->comment('email address the user logs in with');
            $table->timestamp('email_verified_at')->nullable()->comment('when the email address was verified');
            $table->string('password_hash')->nullable()->comment('hashed password, null when the user signs in through SSO');
            $table->string('sso_provider')->nullable()->comment('SSO provider the user signs in with');
            $table->boolean('is_active')->default(true)->comment('whether the user can sign in, so a user can be suspended without being deleted');
            $table->string('locale', 5)->nullable()->comment('language of the interface, falls back to the company locale');
            $table->datetime('last_login_at')->nullable()->comment('when the user last signed in');
            $table->rememberToken()->comment('remember token');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->foreign('owner_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary()->comment('email address of the user');
            $table->string('token')->comment('password reset token');
            $table->timestamp('created_at')->nullable()->comment('when the token was created');
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary()->comment('session id');
            $table->foreignId('user_id')->nullable()->index()->comment('user the session belongs to');
            $table->string('ip_address', 45)->nullable()->comment('ip address of the user');
            $table->text('user_agent')->nullable()->comment('user agent of the browser');
            $table->longText('payload')->comment('session payload');
            $table->integer('last_activity')->index()->comment('when the session was last active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropForeign(['owner_user_id']);
        });

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
