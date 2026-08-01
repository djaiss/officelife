<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Set the photo an employee shows their colleagues. Two square versions are
 * written, one at the size the app displays and one at twice it, so a dense
 * screen has a sharp one to pick from.
 *
 * An employee only ever has one photo, so an earlier one is removed once the
 * new one is in place.
 */
class UpdateEmployeePhoto
{
    /**
     * The mime types we accept. Anything else is rejected, whatever the
     * extension of the uploaded file claims.
     */
    private const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const int MAX_SIZE_IN_BYTES = 5 * 1024 * 1024;

    private ?string $previousPath = null;

    private string $path;

    public function __construct(
        private readonly User $author,
        private readonly Employee $employee,
        private readonly UploadedFile $file,
    ) {}

    public function execute(): Employee
    {
        $this->authorize();
        $this->validate();
        $this->store();
        $this->save();
        $this->removePrevious();
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
     * The file is checked here as well as in the controller, on what it
     * actually is rather than on what its name claims.
     */
    private function validate(): void
    {
        if (! in_array($this->file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('The file must be a jpeg, png or webp image');
        }

        if ($this->file->getSize() > self::MAX_SIZE_IN_BYTES) {
            throw new InvalidArgumentException('The file must not be larger than 5 MB');
        }
    }

    /**
     * The name the employee gave the file never reaches the disk: we generate a
     * random one instead, and the two versions are named after it.
     */
    private function store(): void
    {
        $this->previousPath = $this->employee->photo_path;

        $directory = 'photos/'.$this->employee->id;
        $stem = Str::uuid()->toString();

        foreach (Employee::photoPixelSizes() as $pixels) {
            new ResizeImage(
                file: $this->file,
                width: $pixels,
                height: $pixels,
                path: $directory,
                name: $stem.'_'.$pixels.'.webp',
                disk: $this->diskName(),
            )->execute();
        }

        $this->path = $directory.'/'.$stem;
    }

    private function save(): void
    {
        $this->employee->photo_path = $this->path;
        $this->employee->last_saved_at = now();
        $this->employee->save();
    }

    /**
     * The files of the earlier photo are only removed once the new one is
     * saved, so a failure halfway through leaves the employee with a working
     * photo rather than none.
     */
    private function removePrevious(): void
    {
        if ($this->previousPath === null) {
            return;
        }

        foreach (Employee::photoPixelSizes() as $pixels) {
            $this->disk()->delete($this->previousPath.'_'.$pixels.'.webp');
        }
    }

    /**
     * The disk lives here alone so it can be swapped in one place.
     */
    private function diskName(): string
    {
        return (string) config('filesystems.default');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->author->company,
            user: $this->author,
            action: UserActionEnum::EmployeePhotoUpdate,
            parameters: ['name' => $this->employee->name],
        )->onQueue('low');
    }
}
