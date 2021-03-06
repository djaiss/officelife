<?php

namespace Tests\Unit\ViewHelpers\Team;

use Tests\TestCase;
use App\Models\Company\Ship;
use App\Models\Company\Team;
use App\Helpers\AvatarHelper;
use App\Models\Company\Employee;
use App\Http\ViewHelpers\Team\TeamShowViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TeamShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_a_collection_of_employees(): void
    {
        $michael = $this->createAdministrator();
        $dwight = Employee::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        // create one final employee with a locked status (shouldn't appear in the results)
        Employee::factory()->create([
            'company_id' => $michael->company_id,
            'locked' => true,
        ]);
        $team = Team::factory()->create([
            'company_id' => $michael->company_id,
        ]);

        $team->employees()->attach([$michael->id]);
        $team->employees()->attach([$dwight->id]);

        $collection = TeamShowViewHelper::employees($team);

        $this->assertEquals(2, $collection->count());

        $this->assertEquals(
            [
                0 => [
                    'id' => $michael->id,
                    'name' => $michael->name,
                    'avatar' => AvatarHelper::getImage($michael),
                    'position' => [
                        'id' => $michael->position->id,
                        'title' => $michael->position->title,
                    ],
                    'url' => env('APP_URL').'/'.$michael->company_id.'/employees/'.$michael->id,
                ],
                1 => [
                    'id' => $dwight->id,
                    'name' => $dwight->name,
                    'avatar' => AvatarHelper::getImage($dwight),
                    'position' => [
                        'id' => $dwight->position->id,
                        'title' => $dwight->position->title,
                    ],
                    'url' => env('APP_URL').'/'.$dwight->company_id.'/employees/'.$dwight->id,
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_a_collection_of_recent_ships(): void
    {
        $michael = $this->createAdministrator();
        $team = Team::factory()->create([
            'company_id' => $michael->company_id,
        ]);
        $featureA = Ship::factory()->create([
            'team_id' => $team->id,
        ]);
        $featureB = Ship::factory()->create([
            'team_id' => $team->id,
        ]);
        $featureA->employees()->attach([$michael->id]);
        $collection = TeamShowViewHelper::recentShips($team);

        $this->assertEquals(2, $collection->count());

        $this->assertEquals(
            [
                0 => [
                    'id' => $featureA->id,
                    'title' => $featureA->title,
                    'description' => $featureA->description,
                    'employees' => [
                        0 => [
                            'id' => $michael->id,
                            'name' => $michael->name,
                            'avatar' => AvatarHelper::getImage($michael),
                            'url' => env('APP_URL').'/'.$michael->company_id.'/employees/'.$michael->id,
                        ],
                    ],
                    'url' => route('ships.show', [
                        'company' => $featureA->team->company,
                        'team' => $featureA->team,
                        'ship' => $featureA->id,
                    ]),
                ],
                1 => [
                    'id' => $featureB->id,
                    'title' => $featureB->title,
                    'description' => $featureB->description,
                    'employees' => null,
                    'url' => route('ships.show', [
                        'company' => $featureB->team->company,
                        'team' => $featureB->team,
                        'ship' => $featureB->id,
                    ]),
                ],
            ],
            $collection->toArray()
        );
    }
}
