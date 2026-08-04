<?php

declare(strict_types=1);

use App\Enums\AssetStatusTypeEnum;
use App\Models\AssetStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_statuses', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->nullable()->comment('company the status belongs to, null for the statuses every company gets');
            $table->string('key', 30)->nullable()->comment('identifier the code recognises a system status by, null for a status a company added');
            $table->string('name')->nullable()->comment('name the company gave the status, null for the statuses we ship, which read from a translation key instead');
            $table->string('name_translation_key')->nullable()->comment('key the name is translated from, set on the statuses we ship and never on one a company adds');
            $table->string('type', 20)->comment('how the status behaves, one of the cases of AssetStatusTypeEnum');
            $table->string('color', 7)->nullable()->comment('hex colour the status is shown in');
            $table->boolean('is_system')->default(false)->comment('whether the status is one every company gets and nobody may change');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'name']);
            $table->unique(['company_id', 'name_translation_key']);
        });

        $this->seedSystemStatuses();
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_statuses');
    }

    /**
     * The statuses every company gets are shared rows with no company of their
     * own, so they exist once for the whole installation and cannot drift apart
     * between companies.
     *
     * There is deliberately no Deployed status. An asset holding an active
     * assignment reads as deployed, derived, and the assignment table is the one
     * place that answers it.
     */
    private function seedSystemStatuses(): void
    {
        $statuses = [
            [AssetStatus::READY_TO_DEPLOY, 'Ready to deploy', AssetStatusTypeEnum::Deployable, '#16a34a'],
            [AssetStatus::PENDING, 'Pending', AssetStatusTypeEnum::Pending, '#ca8a04'],
            [AssetStatus::AWAITING_REPAIR, 'Awaiting repair', AssetStatusTypeEnum::Undeployable, '#ea580c'],
            [AssetStatus::LOST, 'Lost', AssetStatusTypeEnum::Undeployable, '#dc2626'],
            [AssetStatus::RETIRED, 'Retired', AssetStatusTypeEnum::Archived, '#6b7280'],
        ];

        foreach ($statuses as [$key, $name, $type, $color]) {
            DB::table('asset_statuses')->insert([
                'company_id' => null,
                'key' => $key,
                'name' => null,
                'name_translation_key' => $name,
                'type' => $type->value,
                'color' => $color,
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
