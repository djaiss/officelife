---
name: view-models
description: Conventions for defining view models. Use when the user wants to create or modify view models, including their structure, relationships, and naming conventions.
---

# View models

- View models are classes that extend the `ViewModel` class, and are responsible for preparing data for the view. They should not contain any business logic, but can contain presentation logic.
- You MUST create a view model for every view that needs data to be displayed, except when the data is really minimal.
- View models are stored in the `app/ViewModels` folder, and are grouped by major domains of the application, like `Account`, `Operate`, `Grow`,... like controllers and views. They MUST match the controller folder structure.
- View models are named after the view they are used for, with the suffix `ViewModel`. For example, the view model for the `account/profile.blade.php` view is `Account/ProfileViewModel`.

## Structure for simple pages

Je ferais simplement un ViewModel par page, dans un seul fichier.

```
app/
└── ViewModels/
    └── Employees/
        └── EmployeeIndexViewModel.php
```

Viewmodel:
```
class EmployeeIndexViewModel
{
    public function __construct(
        private Company $company,
    ) {
    }

    public function employees(): Collection
    {
        return $this->company
            ->employees()
            ->with(['team', 'jobTitle'])
            ->orderBy('last_name')
            ->get();
    }

    public function activeCount(): int
    {
        return $this->company
            ->employees()
            ->active()
            ->count();
    }
}
```

Controller:
```
public function index(): View
{
    return view('employees.index', [
        'viewModel' => new EmployeeIndexViewModel(
            company: currentCompany(),
        ),
    ]);
}
```

View:
```
<p>{{ $viewModel->activeCount() }} employees</p>

@foreach ($viewModel->employees() as $employee)
    ...
@endforeach
```

## Structure for complex pages

- Pour une page riche comme un dashboard, je ferais un ViewModel principal par page, puis je déléguerais les blocs complexes à de petits objets dédiés.

```
app/
└── ViewModels/
    └── Dashboard/
        ├── DashboardViewModel.php
        ├── EmployeeSummary.php
        ├── UpcomingEvents.php
        ├── PendingTasks.php
        └── CompanyMetrics.php
```

Le contrôleur ne connaît que le ViewModel principal
```
return view('dashboard.show', [
    'viewModel' => new DashboardViewModel(
        company: currentCompany(),
        user: auth()->user(),
    ),
]);
```

Viewmodel principal
```
class DashboardViewModel
{
    public function __construct(
        private Company $company,
        private User $user,
    ) {
    }

    public function employeeSummary(): EmployeeSummary
    {
        return new EmployeeSummary($this->company);
    }

    public function upcomingEvents(): UpcomingEvents
    {
        return new UpcomingEvents(
            company: $this->company,
            user: $this->user,
        );
    }

    public function pendingTasks(): PendingTasks
    {
        return new PendingTasks(
            company: $this->company,
            user: $this->user,
        );
    }

    public function companyMetrics(): CompanyMetrics
    {
        return new CompanyMetrics($this->company);
    }
}
```
