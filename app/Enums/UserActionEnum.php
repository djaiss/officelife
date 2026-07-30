<?php

declare(strict_types=1);

namespace App\Enums;

enum UserActionEnum: string
{
    case CompanyCreation = 'company_created';
    case CompanyUpdate = 'company_updated';
    case UserInformationUpdate = 'user_information_updated';
    case UserPasswordUpdate = 'user_password_updated';
    case UserDeletion = 'user_deleted';
    case EmployeeCreation = 'employee_created';
}
