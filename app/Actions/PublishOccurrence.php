<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OccurrenceActorEnum;
use App\Enums\OccurrenceTypeEnum;
use App\Models\Company;
use App\Models\Occurrence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Write down that something happened. This is the one way business code says
 * so.
 */
class PublishOccurrence
{
    private Occurrence $occurrence;

    /** @param  array<string, mixed>  $payload */
    public function __construct(
        private readonly OccurrenceTypeEnum $type,
        private readonly ?Company $company = null,
        private readonly ?Model $subject = null,
        private readonly ?User $actor = null,
        private readonly array $payload = [],
        private readonly ?Carbon $occurredAt = null,
        private readonly string $source = Occurrence::SOURCE_INTERNAL,
    ) {}

    public function execute(): Occurrence
    {
        $this->record();

        return $this->occurrence;
    }

    private function record(): void
    {
        $this->occurrence = Occurrence::query()->create([
            'company_id' => $this->company?->id,
            'type' => $this->type,
            'source' => $this->source,
            'subject_type' => $this->subject === null ? null : $this->subject::class,
            'subject_id' => $this->subject?->getKey(),
            'actor_type' => $this->actor === null ? OccurrenceActorEnum::System : OccurrenceActorEnum::User,
            'actor_id' => $this->actor?->id,
            'payload' => $this->payload === [] ? null : $this->payload,
            'occurred_at' => $this->occurredAt ?? now(),
        ]);
    }
}
