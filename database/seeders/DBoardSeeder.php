<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $view = Permission::findOrCreate('dashboard.view');
        $debug = Permission::findOrCreate('dashboard.debug');

        $roles = [
            'CEO',
            'System Maintenance',
            'COO',
            'CSO',
            'CRM Officer',
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName);
            $role->givePermissionTo($view);
        }

        // Debug is intentionally restricted.
        Role::findOrCreate('CEO')->givePermissionTo($debug);
        Role::findOrCreate('System Maintenance')->givePermissionTo($debug);
    }
}
