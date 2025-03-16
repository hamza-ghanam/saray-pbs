<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        // CEO
        $role = Role::findByName('CEO');
        $role->givePermissionTo([
            'manage roles and permissions',
        ]);

        // System Maintenance
        $role = Role::findByName('System Maintenance');
        $role->givePermissionTo([
            'manage roles and permissions',
        ]);
    }
}
