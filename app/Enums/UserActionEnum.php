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
    case EmployeePhotoUpdate = 'employee_photo_updated';
    case EmployeePhotoDeletion = 'employee_photo_deleted';
    case EmergencyContactUpdate = 'emergency_contact_updated';
    case EmailConfirmation = 'email_confirmed';
    case UserLogin = 'user_logged_in';
    case MagicLinkCreation = 'magic_link_created';

    /**
     * What the action reads as in the logs, written about whoever performed it.
     * The sentence doubles as the translation key, and its placeholders are
     * filled with the parameters the action was logged with.
     */
    public function description(): string
    {
        return match ($this) {
            self::CompanyCreation => 'Created the company called :name',
            self::CompanyUpdate => 'Updated the company called :name',
            self::UserInformationUpdate => 'Updated the account of :email',
            self::UserPasswordUpdate => 'Changed their password',
            self::UserDeletion => 'Deleted the account of :email',
            self::EmployeeCreation => 'Added :name to the company',
            self::EmployeeInformationUpdate => 'Updated the profile of :name',
            self::EmployeePhotoUpdate => 'Changed the photo of :name',
            self::EmployeePhotoDeletion => 'Removed the photo of :name',
            self::EmergencyContactUpdate => 'Updated their emergency contact',
            self::EmailConfirmation => 'Confirmed their email address',
            self::UserLogin => 'Signed in',
            self::MagicLinkCreation => 'Asked for a sign-in link',
        };
    }
}
