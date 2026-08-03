<?php

declare(strict_types=1);

namespace App\Enums;

enum UserActionEnum: string
{
    case CompanyCreation = 'company_created';
    case CompanyUpdate = 'company_updated';
    case UserInformationUpdate = 'user_information_updated';
    case UserPasswordUpdate = 'user_password_updated';
    case UserPreferencesUpdate = 'user_preferences_updated';
    case UserDeletion = 'user_deleted';
    case EmployeeCreation = 'employee_created';
    case EmployeeInformationUpdate = 'employee_information_updated';
    case EmployeePhotoUpdate = 'employee_photo_updated';
    case EmployeePhotoDeletion = 'employee_photo_deleted';
    case EmergencyContactUpdate = 'emergency_contact_updated';
    case LocationCreation = 'location_created';
    case LocationUpdate = 'location_updated';
    case LocationDeletion = 'location_deleted';
    case LocationArchive = 'location_archived';
    case LocationRestoration = 'location_restored';
    case RoleCreation = 'role_created';
    case RoleUpdate = 'role_updated';
    case RoleDeletion = 'role_deleted';
    case RoleAssignment = 'role_assigned';
    case RoleRemoval = 'role_removed';
    case EmailConfirmation = 'email_confirmed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case UserLogin = 'user_logged_in';
    case MagicLinkCreation = 'magic_link_created';
    case ApiKeyCreation = 'api_key_created';
    case ApiKeyDeletion = 'api_key_deleted';
    case ModuleEnabled = 'module_enabled';
    case ModuleDisabled = 'module_disabled';
    case ManufacturerCreation = 'manufacturer_created';
    case ManufacturerUpdate = 'manufacturer_updated';
    case ManufacturerDeletion = 'manufacturer_deleted';
    case AssetCategoryCreation = 'asset_category_created';
    case AssetCategoryUpdate = 'asset_category_updated';
    case AssetCategoryDeletion = 'asset_category_deleted';
    case AssetModelCreation = 'asset_model_created';
    case AssetModelUpdate = 'asset_model_updated';
    case AssetModelDeletion = 'asset_model_deleted';
    case AssetStatusCreation = 'asset_status_created';
    case AssetStatusUpdate = 'asset_status_updated';
    case AssetStatusDeletion = 'asset_status_deleted';
    case AssetCreation = 'asset_created';
    case AssetUpdate = 'asset_updated';
    case AssetDeletion = 'asset_deleted';
    case AssetArchive = 'asset_archived';
    case AssetRestoration = 'asset_restored';
    case AssetCheckout = 'asset_checked_out';
    case AssetCheckin = 'asset_checked_in';

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
            self::UserPreferencesUpdate => 'Changed their preferences',
            self::UserDeletion => 'Deleted the account of :email',
            self::EmployeeCreation => 'Added :name to the company',
            self::EmployeeInformationUpdate => 'Updated the profile of :name',
            self::EmployeePhotoUpdate => 'Changed the photo of :name',
            self::EmployeePhotoDeletion => 'Removed the photo of :name',
            self::EmergencyContactUpdate => 'Updated their emergency contact',
            self::LocationCreation => 'Created the office called :name',
            self::LocationUpdate => 'Updated the office called :name',
            self::LocationDeletion => 'Deleted the office called :name',
            self::LocationArchive => 'Archived the office called :name',
            self::LocationRestoration => 'Reopened the office called :name',
            self::RoleCreation => 'Created the role called :name',
            self::RoleUpdate => 'Changed the role called :name',
            self::RoleDeletion => 'Deleted the role called :name',
            self::RoleAssignment => 'Gave the :name role to :email',
            self::RoleRemoval => 'Took the :name role away from :email',
            self::EmailConfirmation => 'Confirmed their email address',
            self::TwoFactorEnabled => 'Turned two factor authentication on',
            self::TwoFactorDisabled => 'Turned two factor authentication off',
            self::TwoFactorRecoveryCodesRegenerated => 'Asked for new recovery codes',
            self::UserLogin => 'Signed in',
            self::MagicLinkCreation => 'Asked for a sign-in link',
            self::ApiKeyCreation => 'Created the API key called :name',
            self::ApiKeyDeletion => 'Revoked the API key called :name',
            self::ModuleEnabled => 'Turned the :name module on',
            self::ModuleDisabled => 'Turned the :name module off',
            self::ManufacturerCreation => 'Added the manufacturer called :name',
            self::ManufacturerUpdate => 'Updated the manufacturer called :name',
            self::ManufacturerDeletion => 'Deleted the manufacturer called :name',
            self::AssetCategoryCreation => 'Added the equipment category called :name',
            self::AssetCategoryUpdate => 'Updated the equipment category called :name',
            self::AssetCategoryDeletion => 'Deleted the equipment category called :name',
            self::AssetModelCreation => 'Added the equipment model called :name',
            self::AssetModelUpdate => 'Updated the equipment model called :name',
            self::AssetModelDeletion => 'Deleted the equipment model called :name',
            self::AssetStatusCreation => 'Added the equipment status called :name',
            self::AssetStatusUpdate => 'Updated the equipment status called :name',
            self::AssetStatusDeletion => 'Deleted the equipment status called :name',
            self::AssetCreation => 'Added the equipment tagged :tag',
            self::AssetUpdate => 'Updated the equipment tagged :tag',
            self::AssetDeletion => 'Deleted the equipment tagged :tag',
            self::AssetArchive => 'Archived the equipment tagged :tag',
            self::AssetRestoration => 'Brought the equipment tagged :tag back',
            self::AssetCheckout => 'Handed the equipment tagged :tag to :assignee',
            self::AssetCheckin => 'Took the equipment tagged :tag back',
        };
    }
}
