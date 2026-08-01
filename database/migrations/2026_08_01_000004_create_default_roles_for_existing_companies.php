<?php

declare(strict_types=1);

use App\Actions\CreateDefaultRoles;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Give the companies that existed before roles did the same three roles a
     * company created from now on gets, and make each owner an administrator.
     * A company that already has roles is left alone, so this can be run again
     * without doing anything twice.
     */
    public function up(): void
    {
        Company::query()->doesntHave('roles')->chunkById(100, function ($companies): void {
            foreach ($companies as $company) {
                new CreateDefaultRoles(company: $company)->execute();

                if ($company->owner_user_id === null) {
                    continue;
                }

                $administrator = $company->roles()
                    ->where('slug', Role::ADMINISTRATOR)
                    ->firstOrFail();

                $company->owner?->roles()->syncWithoutDetaching([$administrator->id]);
            }
        });
    }

    /**
     * There is nothing to reverse: the roles themselves go when the tables the
     * earlier migrations created are dropped.
     */
    public function down(): void
    {
        //
    }
};
