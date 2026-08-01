<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Remove the photo of an employee, falling them back to their initials.
 */
class DestroyEmployeePhoto
{
    public function __construct(
        private readonly User $author,
        private readonly Employee $employee,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();

        $path = $this->employee->photo_path;

        if ($path === null) {
            return $this->employee;
        }

        $this->employee->photo_path = null;
        $this->employee->last_saved_at = now();
        $this->employee->save();

        // The files go last: deleting one cannot be undone, and a record that
        // points at nothing is a smaller problem than a file nothing points at.
        foreach (Employee::photoPixelSizes() as $pixels) {
            $this->disk()->delete($path.'_'.$pixels.'.webp');
        }

        $this->log();

        return $this->employee;
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::EmployeeUpdate)
            ->forEmployee($this->employee)
            ->authorize();
    }

    /**
     * The disk lives here alone so it can be swapped in one place.
     */
    private function disk(): Filesystem
    {
        return Storage::disk((string) config('filesystems.default'));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::EmployeePhotoDeletion,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
