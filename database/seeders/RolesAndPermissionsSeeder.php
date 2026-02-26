<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions
        $permissions = [
            'view posts',
            'create boost',
            'submit boost',
            'approve boost n1',
            'approve boost n2',
            'reject boost n1',
            'reject boost n2',
            'activate boost',
            'pause boost',
            'view analytics',
            'manage users',
            'manage pages',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Rôle Opérateur (Demandeur)
        $operator = Role::firstOrCreate(['name' => 'operator']);
        $operator->syncPermissions([
            'view posts', 'create boost', 'submit boost', 'view analytics',
        ]);

        // Rôle Validateur N+1
        $validatorN1 = Role::firstOrCreate(['name' => 'validator_n1']);
        $validatorN1->syncPermissions([
            'view posts', 'approve boost n1', 'reject boost n1',
            'activate boost', 'pause boost', 'view analytics',
        ]);

        // Rôle Validateur N+2
        $validatorN2 = Role::firstOrCreate(['name' => 'validator_n2']);
        $validatorN2->syncPermissions([
            'view posts', 'approve boost n2', 'reject boost n2',
            'activate boost', 'pause boost', 'view analytics',
        ]);

        // Garde l'ancien rôle validator pour rétro-compatibilité
        $validator = Role::firstOrCreate(['name' => 'validator']);
        $validator->syncPermissions([
            'view posts', 'approve boost n1', 'reject boost n1',
            'activate boost', 'pause boost', 'view analytics',
        ]);

        // Admin — tout
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());
    }
}
