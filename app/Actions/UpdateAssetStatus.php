<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetStatusTypeEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\AssetStatus;
use App\Models\User;
use InvalidArgumentException;

/**
 * Change a status a company added. The statuses every company gets are not
 * touched: the code recognises them by name, and a company renaming Lost into
 * something else would quietly stop equipment being reported missing.
 */
class UpdateAssetStatus
{
    public function __construct(
        private readonly User $author,
        private readonly AssetStatus $status,
        private string $name,
        private readonly AssetStatusTypeEnum $type,
        private ?string $color = null,
    ) {}

    public function execute(): AssetStatus
    {
        $this->authorize();
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->status;
    }

    private function authorize(): void
    {
        if ($this->status->company === null) {
            throw new InvalidArgumentException('A status every company gets cannot be changed');
        }

        $this->author
            ->permission(PermissionEnum::AssetManage)
            ->forCompany($this->status->company)
            ->authorize();
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->color = TextSanitizer::nullablePlainText($this->color);
    }

    private function validate(): void
    {
        if ($this->status->is_system) {
            throw new InvalidArgumentException('A status every company gets cannot be changed');
        }

        if ($this->name === '') {
            throw new InvalidArgumentException('A status needs a name');
        }

        $taken = AssetStatus::query()
            ->where(function ($query): void {
                $query->where('company_id', $this->status->company_id)->orWhereNull('company_id');
            })
            ->where('name', $this->name)
            ->whereKeyNot($this->status->id)
            ->exists();

        if ($taken) {
            throw new InvalidArgumentException('There is already a status called '.$this->name);
        }
    }

    private function update(): void
    {
        $this->status->name = $this->name;
        $this->status->type = $this->type;
        $this->status->color = $this->color;
        $this->status->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->status->company,
            user: $this->author,
            action: UserActionEnum::AssetStatusUpdate,
            parameters: ['name' => $this->status->name],
        )->onQueue('low');
    }
}
