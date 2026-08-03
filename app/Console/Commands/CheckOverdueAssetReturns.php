<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\DomainEventTypeEnum;
use App\Models\AssetAssignment;
use App\Services\DomainEvents;
use Illuminate\Console\Command;

/**
 * Flag equipment that was due back and has not come back.
 *
 * Each assignment is flagged once rather than every day the condition holds,
 * which is what the stamp on the row is for. Without it, a laptop four months
 * late would say so a hundred and twenty times.
 *
 * What happens next is not this command's business. It publishes the event, and
 * chasing whoever has the equipment is a playbook a company configures.
 */
class CheckOverdueAssetReturns extends Command
{
    protected $signature = 'assets:check-overdue-returns';

    protected $description = 'Flag equipment that is late coming back';

    public function handle(): int
    {
        $assignments = AssetAssignment::query()
            ->with('asset.company')
            ->whereNull('returned_at')
            ->whereNull('overdue_notified_at')
            ->whereNotNull('expected_return_at')
            ->whereDate('expected_return_at', '<', now())
            ->get();

        foreach ($assignments as $assignment) {
            $this->line('Flagging '.$assignment->asset->asset_tag.' as late');
            $this->flag($assignment);
        }

        $this->info($assignments->count().' pieces of equipment flagged as late.');

        return self::SUCCESS;
    }

    private function flag(AssetAssignment $assignment): void
    {
        DomainEvents::publish(
            type: DomainEventTypeEnum::AssetReturnOverdue,
            company: $assignment->asset->company,
            subject: $assignment->asset,
            payload: [
                'tag' => $assignment->asset->asset_tag,
                'expected_return_at' => $assignment->expected_return_at->toDateString(),
                'assignee_type' => $assignment->assignee_type->value,
                'assignee_id' => $assignment->assignee_id,
            ],
        );

        $assignment->overdue_notified_at = now();
        $assignment->save();
    }
}
