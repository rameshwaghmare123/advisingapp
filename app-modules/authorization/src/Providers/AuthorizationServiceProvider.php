<?php

namespace Assist\Authorization\Providers;

use Filament\Panel;
use Assist\Authorization\Models\Role;
use Illuminate\Support\ServiceProvider;
use Assist\Authorization\Models\RoleGroup;
use Assist\Authorization\Models\Permission;
use Assist\Authorization\AuthorizationPlugin;
use Assist\Authorization\AuthorizationRoleRegistry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Assist\Authorization\AuthorizationPermissionRegistry;

class AuthorizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Panel::configureUsing(fn (Panel $panel) => $panel->plugin(new AuthorizationPlugin()));

        $this->app->singleton(AuthorizationPermissionRegistry::class, function ($app) {
            return new AuthorizationPermissionRegistry();
        });

        $this->app->singleton(AuthorizationRoleRegistry::class, function ($app) {
            return new AuthorizationRoleRegistry();
        });
    }

    public function boot(AuthorizationPermissionRegistry $permissionRegistry, AuthorizationRoleRegistry $roleRegistry): void
    {
        Relation::morphMap([
            'role' => Role::class,
            'permission' => Permission::class,
            'role_group' => RoleGroup::class,
        ]);

        $permissionRegistry->registerApiPermissions(
            module: 'authorization',
            path: 'permissions/api/custom'
        );

        $permissionRegistry->registerWebPermissions(
            module: 'authorization',
            path: 'permissions/web/custom'
        );

        $roleRegistry->registerApiRoles(
            module: 'authorization',
            path: 'roles/api'
        );

        $roleRegistry->registerWebRoles(
            module: 'authorization',
            path: 'roles/web'
        );
    }
}
