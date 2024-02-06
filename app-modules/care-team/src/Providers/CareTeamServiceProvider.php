<?php

/*
<COPYRIGHT>

    Copyright © 2016-2024, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\CareTeam\Providers;

use Filament\Panel;
use App\Concerns\GraphSchemaDiscovery;
use Illuminate\Support\ServiceProvider;
use AdvisingApp\CareTeam\CareTeamPlugin;
use AdvisingApp\CareTeam\Models\CareTeam;
use Illuminate\Database\Eloquent\Relations\Relation;
use AdvisingApp\Authorization\AuthorizationRoleRegistry;
use AdvisingApp\Authorization\AuthorizationPermissionRegistry;

class CareTeamServiceProvider extends ServiceProvider
{
    use GraphSchemaDiscovery;

    public function register(): void
    {
        Panel::configureUsing(fn (Panel $panel) => $panel->plugin(new CareTeamPlugin()));
    }

    public function boot(): void
    {
        Relation::morphMap([
            'care_team' => CareTeam::class,
        ]);

        $this->registerRolesAndPermissions();

        $this->discoverSchema(__DIR__ . '/../../graphql/care-team.graphql');
    }

    protected function registerRolesAndPermissions(): void
    {
        $permissionRegistry = app(AuthorizationPermissionRegistry::class);

        $permissionRegistry->registerApiPermissions(
            module: 'care-team',
            path: 'permissions/api/custom'
        );

        $permissionRegistry->registerWebPermissions(
            module: 'care-team',
            path: 'permissions/web/custom'
        );

        $roleRegistry = app(AuthorizationRoleRegistry::class);

        $roleRegistry->registerApiRoles(
            module: 'care-team',
            path: 'roles/api'
        );

        $roleRegistry->registerWebRoles(
            module: 'care-team',
            path: 'roles/web'
        );
    }
}
