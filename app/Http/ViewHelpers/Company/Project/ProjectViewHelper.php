<?php

namespace App\Http\ViewHelpers\Company\Project;

use App\Helpers\DateHelper;
use App\Helpers\AvatarHelper;
use App\Helpers\StringHelper;
use App\Models\Company\Company;
use App\Models\Company\Project;
use App\Models\Company\Employee;

class ProjectViewHelper
{
    /**
     * Array containing the information all the projects in the company.
     *
     * @param Company $company
     * @return array
     */
    public static function index(Company $company): array
    {
        $projects = $company->projects()->orderBy('id', 'desc')->get();

        $projectsCollection = collect([]);
        foreach ($projects as $project) {
            $projectsCollection->push([
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'summary' => $project->summary,
                'status' => $project->status,
                'url' => route('projects.show', [
                    'company' => $company,
                    'project' => $project,
                ]),
            ]);
        }

        return [
            'projects' => $projectsCollection,
        ];
    }

    /**
     * Array containing the information about the project displayed on the
     * summary page.
     *
     * @param Project $project
     * @param Company $company
     * @return array
     */
    public static function summary(Project $project, Company $company): array
    {
        $lead = $project->lead;
        $latestStatus = $project->statuses()->with('author')->latest()->first();

        $links = $project->links;
        $linkCollection = collect([]);
        foreach ($links as $link) {
            $linkCollection->push([
                'id' => $link->id,
                'type' => $link->type,
                'label' => $link->label,
                'url' => $link->url,
            ]);
        }

        $author = $latestStatus->author;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'summary' => $project->summary,
            'status' => $project->status,
            'raw_description' => is_null($project->description) ? null : $project->description,
            'parsed_description' => is_null($project->description) ? null : StringHelper::parse($project->description),
            'latest_update' => is_null($latestStatus) ? null : [
                'title' => $latestStatus->title,
                'status' => $latestStatus->status,
                'description' => StringHelper::parse($latestStatus->description),
                'written_at' => DateHelper::formatDate($latestStatus->created_at),
                'author' => $author ? [
                    'id' => $author->id,
                    'name' => $author->name,
                    'avatar' => AvatarHelper::getImage($author),
                    'position' => (! $author->position) ? null : [
                        'id' => $author->position->id,
                        'title' => $author->position->title,
                    ],
                    'url' => route('employees.show', [
                        'company' => $company,
                        'employee' => $latestStatus->author,
                    ]),
                ] : null,
            ],
            'url_edit' => route('projects.edit', [
                'company' => $company,
                'project' => $project,
            ]),
            'url_delete' => route('projects.delete', [
                'company' => $company,
                'project' => $project,
            ]),
            'project_lead' => $lead ? [
                'id' => $lead->id,
                'name' => $lead->name,
                'avatar' => AvatarHelper::getImage($lead),
                'position' => (! $lead->position) ? null : [
                    'id' => $lead->position->id,
                    'title' => $lead->position->title,
                ],
                'url' => route('employees.show', [
                    'company' => $company,
                    'employee' => $lead,
                ]),
            ] : null,
            'links' => $linkCollection,
        ];
    }

    /**
     * Array containing the information needed to update the project details.
     *
     * @param Project $project
     * @return array
     */
    public static function edit(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'summary' => $project->summary,
        ];
    }

    /**
     * Array containing all the permissions a user can do on the different
     * pages of the project, depending on his role.
     *
     * @param Project $project
     * @param Employee $employee
     * @return array
     */
    public static function permissions(Project $project, Employee $employee): array
    {
        $isInProject = $employee->isInProject($project->id);
        $isProjectLead = is_null($project->lead) ? false : $project->lead->id == $employee->id;

        $canEditLastUpdate = false;
        if ($isProjectLead || $employee->permission_level <= 200) {
            $canEditLastUpdate = true;
        }

        $canManageLinks = false;
        if ($isInProject || $employee->permission_level <= 200) {
            $canManageLinks = true;
        }

        return [
            'can_edit_latest_update' => $canEditLastUpdate,
            'can_manage_links' => $canManageLinks,
        ];
    }

    /**
     * Array containing the information about the project itself.
     *
     * @param Project $project
     * @return array
     */
    public static function info(Project $project): array
    {
        $company = $project->company;

        // get random members of the project
        if ($project->employees->count() > 4) {
            $random = 4;
        } else {
            $random = $project->employees->count();
        }
        $randomMembers = $project->employees->random($random);

        $membersCollection = collect([]);
        foreach ($randomMembers as $member) {
            $membersCollection->push([
                'id' => $member->id,
                'avatar' => AvatarHelper::getImage($member),
                'name' => $member->name,
                'url' => route('employees.show', [
                    'company' => $company,
                    'employee' => $member,
                ]),
            ]);
        }

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'summary' => $project->summary,
            'members' => $membersCollection,
            'other_members_counter' => $project->employees->count() - 4,
        ];
    }
}
