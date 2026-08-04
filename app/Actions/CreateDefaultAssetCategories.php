<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssetCategoryTypeEnum;
use App\Models\AssetCategory;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Give a company a catalogue to start from when it turns the assets module on.
 * Nobody asks for this: without it, recording the first laptop means inventing a
 * category, then a manufacturer, then a model, which is three levels of setup
 * before anything is worth looking at.
 *
 * The categories belong to the company like any other. They can be renamed,
 * added to, and deleted while nothing is filed under them. Nothing in the code
 * knows their names, which is what separates them from the asset statuses: those
 * are seeded because checkout branches on them, these are seeded because typing
 * the word "Laptops" is not work anybody should have to do.
 *
 * Only serialised equipment is offered. Accessories and consumables are a
 * different family with a different life cycle, and nothing can be recorded
 * against them yet.
 */
class CreateDefaultAssetCategories
{
    /** @var Collection<int, AssetCategory> */
    private Collection $categories;

    public function __construct(
        private readonly Company $company,
    ) {}

    /**
     * @return Collection<int, AssetCategory>
     */
    public function execute(): Collection
    {
        $this->categories = new Collection;

        if ($this->company->assetCategories()->exists()) {
            return $this->categories;
        }

        DB::transaction(function (): void {
            foreach ($this->names() as $name) {
                $this->createCategory($name);
            }
        });

        return $this->categories;
    }

    /**
     * What a company of this size usually hands out, and nothing else. A longer
     * list is its own clutter: printers, networking equipment and servers are
     * real for some companies and dead rows for the rest, so they are left to be
     * added by whoever needs them.
     *
     * @return list<string>
     */
    private function names(): array
    {
        return [
            'Laptops',
            'Desktops',
            'Monitors',
            'Phones',
            'Tablets',
            'Docking stations',
            'Security badges',
        ];
    }

    /**
     * The name goes in as a translation key rather than as text, so a category
     * we chose reads in the language of whoever is looking. It becomes plain
     * text the moment a company renames it, because a name they chose is theirs
     * and not ours to translate.
     *
     * Nothing asks to be accepted. Deciding on somebody's behalf what their
     * colleagues have to agree to before holding a laptop is not ours to do.
     */
    private function createCategory(string $name): void
    {
        $this->categories->push(AssetCategory::query()->create([
            'company_id' => $this->company->id,
            'name' => null,
            'name_translation_key' => $name,
            'type' => AssetCategoryTypeEnum::Asset,
            'requires_acceptance' => false,
            'eula_text' => null,
            'send_checkout_email' => false,
        ]));
    }
}
