<?php

namespace App\Http\ViewHelpers\Team;

use App\Helpers\AvatarHelper;
use App\Models\Company\Employee;
use Illuminate\Support\Collection;

class TeamMembersViewHelper
{
    /**
     * Array containing all the basic information about employees that the user
     * has made a search about.
     *
     * @param \Traversable $employees
     *
     * @return Collection
     */
    public static function searchedEmployees(\Traversable $employees): Collection
    {
        $employeesCollection = collect([]);
        foreach ($employees as $employee) {
            $employeesCollection->push([
                'id' => $employee->id,
                'name' => $employee->name,
            ]);
        }

        return $employeesCollection;
    }

    /**
     * Array containing all the information about a specific employee.
     *
     * @param Employee $employee
     *
     * @return array
     */
    public static function employee(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'avatar' => AvatarHelper::getImage($employee),
            'position' => $employee->position,
        ];
    }
}
