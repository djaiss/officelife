<?php

namespace App\Http\Controllers\Company\Adminland;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
use App\Models\Company\ImportJob;
use Illuminate\Http\JsonResponse;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use App\Services\Company\Adminland\File\UploadFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\ViewHelpers\Adminland\AdminUploadEmployeeViewHelper;
use App\Services\Company\Adminland\Employee\ImportEmployeesFromTemporaryTable;
use App\Services\Company\Adminland\Employee\StoreEmployeesFromCSVInTemporaryTable;

class AdminUploadEmployeeController extends Controller
{
    /**
     * Show the list of past import employees by csv.
     *
     * @return Response
     */
    public function index(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $importJobs = AdminUploadEmployeeViewHelper::index($company);

        return Inertia::render('Adminland/Employee/Archives/Index', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'importJobs' => $importJobs,
        ]);
    }

    /**
     * Show the Upload CSV of employees view.
     *
     * @return Response
     */
    public function upload(): Response
    {
        return Inertia::render('Adminland/Employee/Import', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
        ]);
    }

    /**
     * Upload the CSV.
     *
     * @param Request $request
     * @param int $companyId
     * @return JsonResponse
     */
    public function store(Request $request, int $companyId): JsonResponse
    {
        $loggedEmployee = InstanceHelper::getLoggedEmployee();
        $loggedCompany = InstanceHelper::getLoggedCompany();

        $file = (new UploadFile)->execute([
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'file' => $request->file('csv'),
        ]);

        $job = (new StoreEmployeesFromCSVInTemporaryTable)->execute([
            'company_id' => $loggedCompany->id,
            'author_id' => $loggedEmployee->id,
            'path' => $file->path,
        ]);

        return response()->json([
            'url' => route('account.employees.upload.archive.show', [
                'company' => $loggedCompany,
                'archive' => $job,
            ]),
        ]);
    }

    /**
     * Show an import job.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $jobId
     */
    public function show(Request $request, int $companyId, int $jobId)
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();

        try {
            $job = ImportJob::where('company_id', $loggedCompany->id)
                ->findOrFail($jobId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        $details = AdminUploadEmployeeViewHelper::show($job);

        return Inertia::render('Adminland/Employee/Archives/Show', [
            'notifications' => NotificationHelper::getNotifications(InstanceHelper::getLoggedEmployee()),
            'report' => $details,
        ]);
    }

    /**
     * Import employees to the system.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $jobId
     * @return JsonResponse
     */
    public function import(Request $request, int $companyId, int $jobId): JsonResponse
    {
        $loggedCompany = InstanceHelper::getLoggedCompany();
        $loggedEmployee = InstanceHelper::getLoggedEmployee();

        ImportJob::where('company_id', $loggedCompany->id)
            ->findOrFail($jobId);

        (new ImportEmployeesFromTemporaryTable)->execute([
            'company_id' => $companyId,
            'author_id' => $loggedEmployee->id,
            'import_job_id' => $jobId,
        ]);

        return response()->json([
            'data' => true,
        ]);
    }
}
