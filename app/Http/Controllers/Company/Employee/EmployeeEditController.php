<?php

namespace App\Http\Controllers\Company\Employee;

use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
use App\Models\Company\Country;
use App\Models\Company\Employee;
use Illuminate\Http\JsonResponse;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Company\EmployeeStatus;
use App\Services\Company\Place\CreatePlace;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Company\Employee\Birthdate\SetBirthdate;
use App\Services\Company\Employee\HiringDate\SetHiringDate;
use App\Http\ViewHelpers\Employee\EmployeeEditContractViewHelper;
use App\Services\Company\Employee\PersonalDetails\SetSlackHandle;
use App\Services\Company\Employee\Contract\SetContractRenewalDate;
use App\Services\Company\Employee\ConsultantRate\SetConsultantRate;
use App\Services\Company\Employee\PersonalDetails\SetTwitterHandle;
use App\Services\Company\Employee\PersonalDetails\SetPersonalDetails;
use App\Services\Company\Employee\ConsultantRate\DestroyConsultantRate;

class EmployeeEditController extends Controller
{
    /**
     * Show the employee edit page.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return mixed
     */
    public function show(Request $request, int $companyId, int $employeeId)
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        try {
            $employee = Employee::where('company_id', $companyId)
                ->with('status')
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        try {
            $this->asUser(Auth::user())
                ->forEmployee($employee)
                ->forCompanyId($companyId)
                ->asPermissionLevel(config('officelife.permission_level.hr'))
                ->canAccessCurrentPage();
        } catch (\Exception $e) {
            return redirect('/home');
        }

        return Inertia::render('Employee/Edit', [
            'employee' => [
                'id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone_number,
                'birthdate' => (! $employee->birthdate) ? null : [
                    'year' => $employee->birthdate->year,
                    'month' => $employee->birthdate->month,
                    'day' => $employee->birthdate->day,
                ],
                'hired_at' => (! $employee->hired_at) ? null : [
                    'year' => $employee->hired_at->year,
                    'month' => $employee->hired_at->month,
                    'day' => $employee->hired_at->day,
                ],
                'twitter_handle' => $employee->twitter_handle,
                'slack_handle' => $employee->slack_handle,
                'max_year' => Carbon::now()->year,
            ],
            'canEditHiredAt' => $loggedEmployee->permission_level <= 200,
            'canSeeContractInfoTab' => $loggedEmployee->permission_level <= 200 &&
                $loggedEmployee->status ? $loggedEmployee->status->type == EmployeeStatus::EXTERNAL : false,
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
        ]);
    }

    /**
     * Update the information about the employee.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function update(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
        ];

        (new SetPersonalDetails)->execute($data);

        $date = Carbon::createFromDate($request->input('year'), $request->input('month'), $request->input('day'));

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'date' => $date->format('Y-m-d'),
            'year' => intval($request->input('year')),
            'month' => intval($request->input('month')),
            'day' => intval($request->input('day')),
        ];

        (new SetBirthdate)->execute($data);

        if ($request->input('hired_year')) {
            $data = [
                'company_id' => $companyId,
                'author_id' => $loggedEmployee->id,
                'employee_id' => $employeeId,
                'date' => $date->format('Y-m-d'),
                'year' => intval($request->input('hired_year')),
                'month' => intval($request->input('hired_month')),
                'day' => intval($request->input('hired_day')),
            ];

            (new SetHiringDate)->execute($data);
        }

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'twitter' => $request->input('twitter'),
        ];

        (new SetTwitterHandle)->execute($data);

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'slack' => $request->input('slack'),
        ];

        (new SetSlackHandle)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ], 200);
    }

    /**
     * Show the employee edit address page.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|Response
     */
    public function address(Request $request, int $companyId, int $employeeId)
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        try {
            $employee = Employee::where('company_id', $companyId)
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        try {
            $this->asUser(Auth::user())
                ->forEmployee($employee)
                ->forCompanyId($companyId)
                ->asPermissionLevel(config('officelife.permission_level.hr'))
                ->canAccessCurrentPage();
        } catch (\Exception $e) {
            return redirect('/home');
        }

        $countries = Country::select('id', 'name')->get();
        $countriesCollection = collect([]);
        foreach ($countries as $country) {
            $countriesCollection->push([
                'value' => $country->id,
                'label' => $country->name,
            ]);
        }

        return Inertia::render('Employee/Edit/Address', [
            'employee' => $employee->toObject(),
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'canSeeContractInfoTab' => $loggedEmployee->permission_level <= 200 &&
                $loggedEmployee->status ? $loggedEmployee->status->type == EmployeeStatus::EXTERNAL : false,
            'countries' => $countriesCollection,
        ]);
    }

    /**
     * Update the information about the employee's address.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function updateAddress(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'street' => $request->input('street'),
            'city' => $request->input('city'),
            'province' => $request->input('state'),
            'postal_code' => $request->input('postal_code'),
            'country_id' => $request->input('country_id'),
            'placable_id' => $employeeId,
            'placable_type' => 'App\Models\Company\Employee',
            'is_active' => true,
        ];

        (new CreatePlace)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ], 200);
    }

    /**
     * Show the employee edit contract page.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|Response
     */
    public function contract(Request $request, int $companyId, int $employeeId)
    {
        try {
            $employee = Employee::where('company_id', $companyId)
                ->with('consultantRates')
                ->findOrFail($employeeId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        try {
            Employee::where('company_id', $companyId)
                ->where('permission_level', '<=', config('officelife.permission_level.hr'))
                ->findOrFail($loggedEmployee->id);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        return Inertia::render('Employee/Edit/Contract', [
            'employee' => EmployeeEditContractViewHelper::employeeInformation($employee),
            'canSeeContractInfoTab' => $loggedEmployee->permission_level <= 200 &&
                $loggedEmployee->status ? $loggedEmployee->status->type == EmployeeStatus::EXTERNAL : false,
            'rates' => EmployeeEditContractViewHelper::rates($employee, $loggedCompany),
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
        ]);
    }

    /**
     * Update the contract information about the employee.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function updateContractInformation(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'year' => intval($request->input('year')),
            'month' => intval($request->input('month')),
            'day' => intval($request->input('day')),
        ];

        (new SetContractRenewalDate)->execute($data);

        return response()->json([
            'company_id' => $companyId,
        ], 200);
    }

    /**
     * Store the newly created consultant rate.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @return JsonResponse
     */
    public function storeRate(Request $request, int $companyId, int $employeeId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'rate' => intval($request->input('rate')),
        ];

        $rate = (new SetConsultantRate)->execute($data);

        return response()->json([
            'data' => [
                'id' => $rate->id,
                'rate' => $rate->rate,
                'active' => $rate->active,
            ],
        ], 201);
    }

    /**
     * Destroy the given consultant rate.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $employeeId
     * @param int $rateId
     * @return JsonResponse
     */
    public function destroyRate(Request $request, int $companyId, int $employeeId, int $rateId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        $data = [
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'employee_id' => $employeeId,
            'rate_id' => $rateId,
        ];

        (new DestroyConsultantRate)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
