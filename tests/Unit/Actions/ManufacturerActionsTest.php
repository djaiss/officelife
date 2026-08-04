<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateManufacturer;
use App\Actions\DestroyManufacturer;
use App\Actions\UpdateManufacturer;
use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AssetModel;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManufacturerActionsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->withModule(ModuleEnum::Assets)->create();
        $this->author = $this->grant(
            User::factory()->create(['company_id' => $this->company->id]),
            PermissionEnum::AssetManage,
        );
    }

    #[Test]
    public function it_adds_a_manufacturer(): void
    {
        Queue::fake();

        $manufacturer = new CreateManufacturer(
            author: $this->author,
            company: $this->company,
            name: 'Apple',
            websiteUrl: 'https://apple.com',
            supportEmail: 'support@apple.com',
        )->execute();

        $this->assertInstanceOf(Manufacturer::class, $manufacturer);
        $this->assertDatabaseHas('manufacturers', [
            'id' => $manufacturer->id,
            'company_id' => $this->company->id,
            'name' => 'Apple',
            'support_email' => 'support@apple.com',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ManufacturerCreation,
        );
    }

    #[Test]
    public function it_adds_a_manufacturer_with_nothing_but_a_name(): void
    {
        Queue::fake();

        $manufacturer = new CreateManufacturer(author: $this->author, company: $this->company, name: 'Dell')->execute();

        $this->assertNull($manufacturer->website_url);
        $this->assertNull($manufacturer->support_email);
    }

    #[Test]
    public function it_throws_when_the_company_already_knows_that_manufacturer(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Manufacturer::factory()->create(['company_id' => $this->company->id, 'name' => 'Apple']);

        new CreateManufacturer(author: $this->author, company: $this->company, name: 'Apple')->execute();
    }

    #[Test]
    public function it_lets_another_company_know_the_same_manufacturer(): void
    {
        Queue::fake();

        Manufacturer::factory()->create(['name' => 'Apple']);

        $manufacturer = new CreateManufacturer(author: $this->author, company: $this->company, name: 'Apple')->execute();

        $this->assertEquals('Apple', $manufacturer->name);
    }

    #[Test]
    public function it_changes_a_manufacturer(): void
    {
        Queue::fake();

        $manufacturer = Manufacturer::factory()->create(['company_id' => $this->company->id]);

        new UpdateManufacturer(
            author: $this->author,
            manufacturer: $manufacturer,
            name: 'Lenovo',
            supportPhone: '555-0100',
        )->execute();

        $this->assertDatabaseHas('manufacturers', [
            'id' => $manufacturer->id,
            'name' => 'Lenovo',
            'support_phone' => '555-0100',
        ]);
    }

    #[Test]
    public function it_lets_a_manufacturer_keep_its_own_name_when_changed(): void
    {
        Queue::fake();

        $manufacturer = Manufacturer::factory()->create(['company_id' => $this->company->id, 'name' => 'Apple']);

        new UpdateManufacturer(author: $this->author, manufacturer: $manufacturer, name: 'Apple')->execute();

        $this->assertEquals('Apple', $manufacturer->fresh()->name);
    }

    #[Test]
    public function it_deletes_a_manufacturer_nothing_points_at(): void
    {
        Queue::fake();

        $manufacturer = Manufacturer::factory()->create(['company_id' => $this->company->id]);

        new DestroyManufacturer(author: $this->author, manufacturer: $manufacturer)->execute();

        $this->assertModelMissing($manufacturer);
    }

    #[Test]
    public function it_refuses_to_delete_a_manufacturer_that_still_makes_something(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Queue::fake();

        $manufacturer = Manufacturer::factory()->create(['company_id' => $this->company->id]);
        AssetModel::factory()->create([
            'company_id' => $this->company->id,
            'manufacturer_id' => $manufacturer->id,
        ]);

        new DestroyManufacturer(author: $this->author, manufacturer: $manufacturer)->execute();
    }

    #[Test]
    public function it_throws_when_the_author_may_not_manage_equipment(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateManufacturer(
            author: User::factory()->create(['company_id' => $this->company->id]),
            company: $this->company,
            name: 'Apple',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_company_has_not_turned_the_module_on(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $author = $this->grant(
            User::factory()->create(['company_id' => $company->id]),
            PermissionEnum::AssetManage,
        );

        new CreateManufacturer(author: $author, company: $company, name: 'Apple')->execute();
    }
}
