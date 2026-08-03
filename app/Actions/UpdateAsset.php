<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DomainEventTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Location;
use App\Models\User;
use App\Services\DomainEvents;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Change a piece of equipment the company owns.
 *
 * Moving it into a status that means lost is the one change that says something
 * happened rather than that a field was corrected, so that one publishes an
 * event and the rest do not.
 */
class UpdateAsset
{
    private bool $wasReportedLost = false;

    public function __construct(
        private readonly User $author,
        private readonly Asset $asset,
        private readonly AssetModel $assetModel,
        private readonly AssetStatus $status,
        private string $assetTag,
        private ?string $serialNumber = null,
        private ?string $name = null,
        private readonly ?Location $defaultLocation = null,
        private readonly ?Carbon $purchaseDate = null,
        private readonly ?int $purchaseCost = null,
        private ?string $orderNumber = null,
        private readonly ?Carbon $warrantyExpiresAt = null,
        private readonly ?Carbon $endOfLifeAt = null,
        private readonly bool $isByod = false,
        private readonly bool $isRequestable = false,
        private ?string $notes = null,
    ) {}

    public function execute(): Asset
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->publish();
        $this->log();

        return $this->asset;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->asset->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->assetTag = TextSanitizer::plainText($this->assetTag);
        $this->serialNumber = TextSanitizer::nullablePlainText($this->serialNumber);
        $this->name = TextSanitizer::nullablePlainText($this->name);
        $this->orderNumber = TextSanitizer::nullablePlainText($this->orderNumber);
        $this->notes = TextSanitizer::nullablePlainText($this->notes);
    }

    private function validate(): void
    {
        if ($this->assetTag === '') {
            throw new InvalidArgumentException('A piece of equipment needs a tag');
        }

        if ($this->assetModel->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The model belongs to another company');
        }

        if ($this->defaultLocation !== null && $this->defaultLocation->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The office belongs to another company');
        }

        if ($this->status->company_id !== null && $this->status->company_id !== $this->asset->company_id) {
            throw new InvalidArgumentException('The status belongs to another company');
        }

        $taken = Asset::query()
            ->where('company_id', $this->asset->company_id)
            ->where('asset_tag', $this->assetTag)
            ->whereKeyNot($this->asset->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already tags something '.$this->assetTag);
        }
    }

    private function update(): void
    {
        $this->wasReportedLost = ! $this->asset->status->meansLost() && $this->status->meansLost();

        $this->asset->asset_model_id = $this->assetModel->id;
        $this->asset->status_id = $this->status->id;
        $this->asset->asset_tag = $this->assetTag;
        $this->asset->serial_number = $this->serialNumber;
        $this->asset->name = $this->name;
        $this->asset->default_location_id = $this->defaultLocation?->id;
        $this->asset->purchase_date = $this->purchaseDate;
        $this->asset->purchase_cost = $this->purchaseCost;
        $this->asset->order_number = $this->orderNumber;
        $this->asset->warranty_expires_at = $this->warrantyExpiresAt;
        $this->asset->end_of_life_at = $this->endOfLifeAt;
        $this->asset->is_byod = $this->isByod;
        $this->asset->is_requestable = $this->isRequestable;
        $this->asset->notes = $this->notes;
        $this->asset->save();
    }

    private function publish(): void
    {
        if (! $this->wasReportedLost) {
            return;
        }

        DomainEvents::publish(
            type: DomainEventTypeEnum::AssetReportedLost,
            company: $this->asset->company,
            subject: $this->asset,
            actor: $this->author,
            payload: ['tag' => $this->asset->asset_tag],
        );
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->asset->company,
            user: $this->author,
            action: UserActionEnum::AssetUpdate,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }
}
