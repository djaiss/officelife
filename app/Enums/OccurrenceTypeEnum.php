<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The catalogue of things that happen, which playbooks will one day be
 * configured to react to. The convention is entity.action, the same shape as the
 * permissions.
 *
 * A type is added along with the code that publishes it. A type nothing
 * publishes is a trigger somebody can configure that will never fire.
 */
enum OccurrenceTypeEnum: string
{
    case CompanyCreated = 'company.created';
    case CompanyUpdated = 'company.updated';
    case UserCreated = 'user.created';
    case EmployeeCreated = 'employee.created';
    case OfficeCreated = 'office.created';
    case OfficeArchived = 'office.archived';
    case OfficeReopened = 'office.reopened';
    case AssetCheckedOut = 'asset.checked_out';
    case AssetCheckedIn = 'asset.checked_in';
    case AssetReturnOverdue = 'asset.return_overdue';
    case AssetReportedLost = 'asset.reported_lost';

    /**
     * The module the type belongs to, or null when the core publishes it. A
     * module that is off publishes nothing, and its types are not offered when
     * somebody picks what a playbook reacts to.
     */
    public function module(): ?ModuleEnum
    {
        return match ($this) {
            self::AssetCheckedOut,
            self::AssetCheckedIn,
            self::AssetReturnOverdue,
            self::AssetReportedLost => ModuleEnum::Assets,
            self::CompanyCreated,
            self::CompanyUpdated,
            self::UserCreated,
            self::EmployeeCreated,
            self::OfficeCreated,
            self::OfficeArchived,
            self::OfficeReopened => null,
        };
    }
}
