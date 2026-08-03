<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Company;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Record a piece of equipment the company owns.
 *
 * The tag is what the company writes on the label and has to be its own. The
 * serial number is what the manufacturer stamped on it, which is not unique:
 * two vendors can stamp the same string, and a machine can arrive with an
 * unreadable one.
 */
class CreateAsset
{
    private Asset $asset;

    public function __construct(
        private readonly User $author,
        private readonly Company $company,
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
        $this->create();
        $this->log();

        return $this->asset;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->company)
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

        if ($this->assetModel->company_id !== $this->company->id) {
            throw new InvalidArgumentException('The model belongs to another company');
        }

        if ($this->defaultLocation !== null && $this->defaultLocation->company_id !== $this->company->id) {
            throw new InvalidArgumentException('The office belongs to another company');
        }

        $this->validateStatus();

        $taken = Asset::query()
            ->where('company_id', $this->company->id)
            ->where('asset_tag', $this->assetTag)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('The company already tags something '.$this->assetTag);
        }
    }

    /**
     * A status belongs either to everybody, in which case it has no company, or
     * to this company. One belonging to somebody else is not a status this
     * company has.
     */
    private function validateStatus(): void
    {
        if ($this->status->company_id !== null && $this->status->company_id !== $this->company->id) {
            throw new InvalidArgumentException('The status belongs to another company');
        }
    }

    private function create(): void
    {
        $this->asset = Asset::query()->create([
            'company_id' => $this->company->id,
            'asset_model_id' => $this->assetModel->id,
            'status_id' => $this->status->id,
            'asset_tag' => $this->assetTag,
            'serial_number' => $this->serialNumber,
            'name' => $this->name,
            'default_location_id' => $this->defaultLocation?->id,
            'current_location_id' => $this->defaultLocation?->id,
            'purchase_date' => $this->purchaseDate,
            'purchase_cost' => $this->purchaseCost,
            'order_number' => $this->orderNumber,
            'warranty_expires_at' => $this->warrantyExpiresAt,
            'end_of_life_at' => $this->endOfLifeAt,
            'is_byod' => $this->isByod,
            'is_requestable' => $this->isRequestable,
            'notes' => $this->notes,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::AssetCreation,
            parameters: ['tag' => $this->asset->asset_tag],
        )->onQueue('low');
    }
}
