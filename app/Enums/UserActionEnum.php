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
    case EmployeeInformationUpdate = 'employee_information_updated';
    case EmergencyContactUpdate = 'emergency_contact_updated';
    case EmailConfirmation = 'email_confirmed';
    case UserLogin = 'user_logged_in';
    case MagicLinkCreation = 'magic_link_created';
}
