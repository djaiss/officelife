<?php

declare(strict_types=1);

namespace App\Enums;

enum UserActionEnum: string
{
    case CompanyCreated = 'company_created';
    case CompanyUpdated = 'company_updated';
    case UserInformationUpdated = 'user_information_updated';
    case UserPasswordUpdated = 'user_password_updated';
    case UserPreferencesUpdated = 'user_preferences_updated';
    case UserDeleted = 'user_deleted';
    case EmployeeCreated = 'employee_created';
    case EmployeeInformationUpdated = 'employee_information_updated';
    case EmployeeAvatarUpdated = 'employee_avatar_updated';
    case EmployeeAvatarDeleted = 'employee_avatar_deleted';
    case EmergencyContactUpdated = 'emergency_contact_updated';
    case OfficeCreated = 'office_created';
    case OfficeUpdated = 'office_updated';
    case OfficeDeleted = 'office_deleted';
    case OfficeArchived = 'office_archived';
    case OfficeRestored = 'office_restored';
    case RoleCreated = 'role_created';
    case RoleUpdated = 'role_updated';
    case RoleDeleted = 'role_deleted';
    case RoleAssigned = 'role_assigned';
    case RoleRemoved = 'role_removed';
    case EmailConfirmed = 'email_confirmed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case UserSignedIn = 'user_signed_in';
    case MagicLinkCreated = 'magic_link_created';
    case ApiKeyCreated = 'api_key_created';
    case ApiKeyDeleted = 'api_key_deleted';
    case ModuleEnabled = 'module_enabled';
    case ModuleDisabled = 'module_disabled';
    case ManufacturerCreated = 'manufacturer_created';
    case ManufacturerUpdated = 'manufacturer_updated';
    case ManufacturerDeleted = 'manufacturer_deleted';
    case AssetCategoryCreated = 'asset_category_created';
    case AssetCategoryUpdated = 'asset_category_updated';
    case AssetCategoryDeleted = 'asset_category_deleted';
    case AssetModelCreated = 'asset_model_created';
    case AssetModelUpdated = 'asset_model_updated';
    case AssetModelDeleted = 'asset_model_deleted';
    case AssetStatusCreated = 'asset_status_created';
    case AssetStatusUpdated = 'asset_status_updated';
    case AssetStatusDeleted = 'asset_status_deleted';
    case AssetCreated = 'asset_created';
    case AssetUpdated = 'asset_updated';
    case AssetDeleted = 'asset_deleted';
    case AssetArchived = 'asset_archived';
    case AssetRestored = 'asset_restored';
    case AssetCheckedOut = 'asset_checked_out';
    case AssetCheckedIn = 'asset_checked_in';

    public function description(): string
    {
        return match ($this) {
            self::CompanyCreated => 'Created the company called :name',
            self::CompanyUpdated => 'Updated the company called :name',
            self::UserInformationUpdated => 'Updated the account of :email',
            self::UserPasswordUpdated => 'Changed their password',
            self::UserPreferencesUpdated => 'Changed their preferences',
            self::UserDeleted => 'Deleted the account of :email',
            self::EmployeeCreated => 'Added :name to the company',
            self::EmployeeInformationUpdated => 'Updated the profile of :name',
            self::EmployeeAvatarUpdated => 'Changed the avatar of :name',
            self::EmployeeAvatarDeleted => 'Removed the avatar of :name',
            self::EmergencyContactUpdated => 'Updated their emergency contact',
            self::OfficeCreated => 'Created the office called :name',
            self::OfficeUpdated => 'Updated the office called :name',
            self::OfficeDeleted => 'Deleted the office called :name',
            self::OfficeArchived => 'Archived the office called :name',
            self::OfficeRestored => 'Reopened the office called :name',
            self::RoleCreated => 'Created the role called :name',
            self::RoleUpdated => 'Changed the role called :name',
            self::RoleDeleted => 'Deleted the role called :name',
            self::RoleAssigned => 'Gave the :name role to :email',
            self::RoleRemoved => 'Took the :name role away from :email',
            self::EmailConfirmed => 'Confirmed their email address',
            self::TwoFactorEnabled => 'Turned two factor authentication on',
            self::TwoFactorDisabled => 'Turned two factor authentication off',
            self::TwoFactorRecoveryCodesRegenerated => 'Asked for new recovery codes',
            self::UserSignedIn => 'Signed in',
            self::MagicLinkCreated => 'Asked for a sign-in link',
            self::ApiKeyCreated => 'Created the API key called :name',
            self::ApiKeyDeleted => 'Revoked the API key called :name',
            self::ModuleEnabled => 'Turned the :name module on',
            self::ModuleDisabled => 'Turned the :name module off',
            self::ManufacturerCreated => 'Added the manufacturer called :name',
            self::ManufacturerUpdated => 'Updated the manufacturer called :name',
            self::ManufacturerDeleted => 'Deleted the manufacturer called :name',
            self::AssetCategoryCreated => 'Added the asset category called :name',
            self::AssetCategoryUpdated => 'Updated the asset category called :name',
            self::AssetCategoryDeleted => 'Deleted the asset category called :name',
            self::AssetModelCreated => 'Added the asset model called :name',
            self::AssetModelUpdated => 'Updated the asset model called :name',
            self::AssetModelDeleted => 'Deleted the asset model called :name',
            self::AssetStatusCreated => 'Added the asset status called :name',
            self::AssetStatusUpdated => 'Updated the asset status called :name',
            self::AssetStatusDeleted => 'Deleted the asset status called :name',
            self::AssetCreated => 'Added the asset tagged :tag',
            self::AssetUpdated => 'Updated the asset tagged :tag',
            self::AssetDeleted => 'Deleted the asset tagged :tag',
            self::AssetArchived => 'Archived the asset tagged :tag',
            self::AssetRestored => 'Brought the asset tagged :tag back',
            self::AssetCheckedOut => 'Handed the asset tagged :tag to :assignee',
            self::AssetCheckedIn => 'Took the asset tagged :tag back',
        };
    }
}
