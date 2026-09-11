<?php

declare(strict_types=1);

namespace App\Enums;

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
