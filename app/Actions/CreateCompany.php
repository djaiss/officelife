<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PlanEnum;
use App\Helpers\TextSanitizer;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Create a company and its first user, who becomes the owner of the company.
 */
class CreateCompany
{
    private Company $company;

    private User $owner;

    public function __construct(
        private string $name,
        private string $email,
        private readonly string $password,
        private readonly PlanEnum $plan = PlanEnum::Free,
    ) {}

    public function execute(): Company
    {
        $this->sanitize();

        DB::transaction(function (): void {
            $this->createCompany();
            $this->createOwner();
            $this->attachOwner();
        });

        return $this->company;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->email = mb_strtolower(TextSanitizer::plainText($this->email));
    }

    private function createCompany(): void
    {
        $this->company = Company::query()->create([
            'name' => $this->name,
            'slug' => $this->slug(),
            'plan' => $this->plan,
            'timezone' => 'UTC',
            'locale' => 'en',
            'is_self_hosted' => false,
            'trial_ends_at' => now()->addDays(30),
        ]);
    }

    private function createOwner(): void
    {
        $this->owner = new CreateUser(
            company: $this->company,
            email: $this->email,
            password: $this->password,
        )->execute();
    }

    private function attachOwner(): void
    {
        $this->company->owner_user_id = $this->owner->id;
        $this->company->save();
    }

    /**
     * Build a slug out of the company name, suffixed with a number when another
     * company already took it.
     */
    private function slug(): string
    {
        $base = Str::slug($this->name);
        $base = $base === '' ? 'company' : $base;

        $slug = $base;
        $suffix = 1;

        while (Company::withTrashed()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
