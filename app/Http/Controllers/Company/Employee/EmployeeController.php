<?php

namespace App\Http\Controllers\Company\Employee;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\AvatarHelper;
use App\Helpers\InstanceHelper;
use App\Models\Company\Employee;
use Illuminate\Http\JsonResponse;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Services\Company\Employee\Manager\AssignManager;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\ViewHelpers\Employee\EmployeeShowViewHelper;
use App\Services\Company\Employee\Manager\UnassignManager;

class EmployeeController extends Controller
{
    /**
     * Display the list of employees.
     *
     * @param Request $request
     * @param int $companyId
     * @return Response
     */
    public function index(Request $request, int $companyId): Response
    {
        $company = InstanceHelper::getLoggedCompany();

        $employees = $company->employees()
            ->with('teams')
            ->with('position')
            ->notLocked()
            ->orderBy('last_name', 'asc')
            ->get();

        $employeesCollection = collect([]);
        foreach ($employees as $employee) {
            $employeesCollection->push([
                'id' => $employee->id,
                'name' => $employee->name,
                'avatar' => AvatarHelper::getImage($employee),
                'teams' => $employee->teams,
                'position' => (! $employee->position) ? null : [
                    'id' => $employee->position->id,
                    'title' => $employee->position->title,
                ],
            ]);
        }

        return Inertia::render('Employee/Index', [
            'employees' => $employeesCollection,
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
        ]);
    }

    /**
     * Display the detail of an employee.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return mixed
     */
    public function show(Request $request, int $companyId, int $employeeId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        try {
            $employee = Employee::where('company_id', $companyId)
                ->where('id', $employeeId)
                ->with('company')
                ->with('user')
                ->with('status')
                ->with('places')
                ->with('managers')
                ->with('ships')
                ->with('skills')
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        // information about what the logged employee can do
        $permissions = EmployeeShowViewHelper::permissions($loggedEmployee, $employee);

        // managers
        $managersOfEmployee = EmployeeShowViewHelper::managers($employee);

        // direct reports
        $directReportsOfEmployee = EmployeeShowViewHelper::directReports($employee);

        // questions
        $questions = EmployeeShowViewHelper::questions($employee);

        // all the teams the employee belongs to
        $employeeTeams = EmployeeShowViewHelper::teams($employee->teams, $company);

        // all skills of this employee
        $skills = EmployeeShowViewHelper::skills($employee);

        // all eCoffee session of this employee
        $ecoffees = EmployeeShowViewHelper::eCoffees($employee, $company);

        // information about the employee that the logged employee consults, that depends on what the logged Employee has the right to see
        $employee = EmployeeShowViewHelper::informationAboutEmployee($employee, $permissions);

        return Inertia::render('Employee/Show', [
            'menu' => 'presentation',
            'employee' => $employee,
            'permissions' => $permissions,
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'managersOfEmployee' => $managersOfEmployee,
            'directReports' => $directReportsOfEmployee,
            'questions' => $questions,
            'teams' => $employeeTeams,
            'skills' => $skills,
            'ecoffees' => $ecoffees,
        ]);
    }

    /**
     * Assign a manager to the employee.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function assignManager(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'manager_id' => $request->input('id'),
        ];

        $manager = (new AssignManager)->execute($data);

        return response()->json([
            'data' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'avatar' => AvatarHelper::getImage($manager),
                'position' => (! $manager->position) ? null : [
                    'id' => $manager->position->id,
                    'title' => $manager->position->title,
                ],
                'url' => route('employees.show', [
                    'company' => $manager->company,
                    'employee' => $manager,
                ]),
            ],
        ], 200);
    }

    /**
     * Assign a direct report to the employee.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function assignDirectReport(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $request->input('id'),
            'manager_id' => $employeeId,
        ];

        (new AssignManager)->execute($data);

        $directReport = Employee::findOrFail($request->input('id'));

        return response()->json([
            'data' =>[
                'id' => $directReport->id,
                'name' => $directReport->name,
                'avatar' => AvatarHelper::getImage($directReport),
                'position' => (! $directReport->position) ? null : [
                    'id' => $directReport->position->id,
                    'title' => $directReport->position->title,
                ],
                'url' => route('employees.show', [
                    'company' => $directReport->company,
                    'employee' => $directReport,
                ]),
            ],
        ], 200);
    }

    /**
     * Unassign a manager.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function unassignManager(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'manager_id' => $request->input('id'),
        ];

        $manager = (new UnassignManager)->execute($data);

        return response()->json([
            'data' => [
                'id' => $manager->id,
            ],
        ], 200);
    }

    /**
     * Unassign a direct report.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $managerId
     * @return JsonResponse
     */
    public function unassignDirectReport(Request $request, int $companyId, int $managerId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $request->input('id'),
            'manager_id' => $managerId,
        ];

        $manager = (new UnassignManager)->execute($data);

        return response()->json([
            'data' => [
                'id' => $manager->id,
            ],
        ], 200);
    }
}
