<?php

namespace App\Http\Controllers\Company\Dashboard;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Helpers\InstanceHelper;
use App\Models\Company\Company;
use App\Models\Company\Expense;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\ViewHelpers\Dashboard\DashboardExpenseViewHelper;
use App\Services\Company\Employee\Expense\AcceptExpenseAsAccountant;
use App\Services\Company\Employee\Expense\RejectExpenseAsAccountant;

/**
 * This is the controller showing the Expenses tab for the Accountant role.
 */
class DashboardExpensesController extends Controller
{
    /**
     * Display all expenses in the company.
     *
     * @return Response
     */
    public function index(): Response
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        $awaitingAccountingExpenses = DashboardExpenseViewHelper::waitingForAccountingApproval($company);
        $awaitingManagerExpenses = DashboardExpenseViewHelper::waitingForManagerApproval($company);
        $acceptedOrRejected = DashboardExpenseViewHelper::acceptedAndRejected($company);

        $employeeInformation = [
            'id' => $employee->id,
            'dashboard_view' => 'expenses',
            'is_manager' => $employee->directReports->count() > 0,
            'can_manage_expenses' => $employee->can_manage_expenses,
            'can_manage_hr' => $employee->permission_level <= config('officelife.permission_level.hr'),
        ];

        return Inertia::render('Dashboard/Expenses/Index', [
            'employee' => $employeeInformation,
            'awaitingAccountingExpenses' => $awaitingAccountingExpenses,
            'awaitingManagerExpenses' => $awaitingManagerExpenses,
            'acceptedOrRejected' => $acceptedOrRejected,
            'notifications' => NotificationHelper::getNotifications($employee),
        ]);
    }

    /**
     * Show the expense to validate.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $expenseId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|Response
     */
    public function show(Request $request, int $companyId, int $expenseId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        // can this expense been seen by someone in this company?
        try {
            $expense = Expense::where('company_id', $company->id)
                ->findOrFail($expenseId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        if ($expense->status !== Expense::AWAITING_ACCOUTING_APPROVAL) {
            return redirect('home');
        }

        return Inertia::render('Dashboard/Expenses/Approve', [
            'expense' => DashboardExpenseViewHelper::expense($expense),
            'notifications' => NotificationHelper::getNotifications($employee),
        ]);
    }

    /**
     * Show the summary of the expense.
     * Only the employees with accountant role, and the employee who submitted
     * the expense, can see this.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $expenseId
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|Response
     */
    public function summary(Request $request, int $companyId, int $expenseId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        // can this expense been seen by someone in this company?
        try {
            $expense = Expense::where('company_id', $company->id)
                ->with('employee')
                ->with('managerApprover')
                ->with('employee.position')
                ->with('employee.status')
                ->findOrFail($expenseId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        return Inertia::render('Dashboard/Expenses/Show', [
            'expense' => DashboardExpenseViewHelper::expense($expense),
            'notifications' => NotificationHelper::getNotifications($employee),
        ]);
    }

    /**
     * Accept the expense.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $expenseId
     * @return mixed
     */
    public function accept(Request $request, int $companyId, int $expenseId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        // can this expense been seen by someone in this company?
        try {
            $expense = Expense::where('company_id', $company->id)
                ->findOrFail($expenseId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        if ($expense->status !== Expense::AWAITING_ACCOUTING_APPROVAL) {
            return redirect('home');
        }

        $data = [
            'company_id' => $company->id,
            'author_id' => $employee->id,
            'expense_id' => $expenseId,
        ];

        $expense = (new AcceptExpenseAsAccountant)->execute($data);

        return response()->json([
            'data' => $expense->id,
        ], 201);
    }

    /**
     * Reject the expense.
     *
     * @param Request $request
     * @param int $companyId
     * @param int $expenseId
     * @return mixed
     */
    public function reject(Request $request, int $companyId, int $expenseId)
    {
        $company = InstanceHelper::getLoggedCompany();
        $employee = InstanceHelper::getLoggedEmployee();

        // can this expense been seen by someone in this company?
        try {
            $expense = Expense::where('company_id', $company->id)
                ->findOrFail($expenseId);
        } catch (ModelNotFoundException $e) {
            return redirect('home');
        }

        if ($expense->status !== Expense::AWAITING_ACCOUTING_APPROVAL) {
            return redirect('home');
        }

        $data = [
            'company_id' => $company->id,
            'author_id' => $employee->id,
            'expense_id' => $expenseId,
            'reason' => $request->input('reason'),
        ];

        $expense = (new RejectExpenseAsAccountant)->execute($data);

        return response()->json([
            'data' => $expense->id,
        ], 201);
    }
}
