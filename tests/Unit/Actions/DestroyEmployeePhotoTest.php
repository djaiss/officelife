<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyEmployeePhoto;
use App\Actions\UpdateEmployeePhoto;
use App\Enums\PermissionEnum;
use App\Enums\ScopeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyEmployeePhotoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_the_photo_and_its_files(): void
    {
        Queue::fake();
        Storage::fake('local');

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Kevin',
            'last_name' => 'Malone',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        $withPhoto = new UpdateEmployeePhoto(
            author: $user,
            employee: $employee,
            file: UploadedFile::fake()->image('chili.jpg', 400, 400),
        )->execute();

        $path = (string) $withPhoto->photo_path;

        $result = new DestroyEmployeePhoto(
            author: $user->fresh(),
            employee: $employee->fresh(),
        )->execute();

        $this->assertInstanceOf(Employee::class, $result);
        $this->assertNull($result->photo_path);
        $this->assertFalse($result->hasPhoto());

        foreach (Employee::photoPixelSizes() as $pixels) {
            Storage::disk('local')->assertMissing($path.'_'.$pixels.'.webp');
        }

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'photo_path' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmployeePhotoDeletion,
        );
    }

    #[Test]
    public function it_does_nothing_when_there_is_no_photo(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'photo_path' => null,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
        ]);
        $this->grant($user, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        $result = new DestroyEmployeePhoto(author: $user, employee: $employee)->execute();

        $this->assertNull($result->photo_path);

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_throws_when_the_author_may_only_change_their_own_photo(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $angela = Employee::factory()->create(['company_id' => $company->id]);
        $oscar = Employee::factory()->create(['company_id' => $company->id]);
        $author = User::factory()->create([
            'company_id' => $company->id,
            'employee_id' => $angela->id,
        ]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Self);

        new DestroyEmployeePhoto(author: $author, employee: $oscar)->execute();
    }

    #[Test]
    public function it_throws_when_the_employee_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $dunderMifflin = Company::factory()->create();
        $michaelScottPaperCompany = Company::factory()->create();
        $stranger = Employee::factory()->create(['company_id' => $michaelScottPaperCompany->id]);
        $author = User::factory()->create(['company_id' => $dunderMifflin->id]);
        $this->grant($author, PermissionEnum::EmployeeUpdate, ScopeEnum::Company);

        new DestroyEmployeePhoto(author: $author, employee: $stranger)->execute();
    }
}
