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
        /*
        $role = Role::findByName('CRM Officer');
        $role->givePermissionTo([
            'add building', 'edit building', 'delete building', 'view building',
            'add unit', 'edit unit', 'delete unit', 'view unit', 'search unit',
            'generate one-time link',
        ]);

        $role = Role::findByName('Sales');
        $role->givePermissionTo([
            'view building',
            'view unit', 'search unit',
            'book unit', 'hold unit', 'generate sales offer', 'generate reservation form',
        ]);

        $role = Role::findByName('CSO');
        $role->givePermissionTo([
            'approve unit', 'approve booking'
        ]);

        $role = Role::findByName('CFO');
        $role->givePermissionTo([
            'approve reservation form',
        ]);

        $role = Role::findByName('Accountant');
        $role->givePermissionTo([
            'approve booking',
        ]);

        $role = Role::findByName('HR Admin');
        $role->givePermissionTo([
            'generate one-time link', 'approve registration',
            'add sales', 'add crm officer', 'add accountant', 'add hr admin',
        ]);

        $role = Role::findByName('Broker');
        $role->givePermissionTo([
            'view building',
            'view unit', 'search unit',
            'generate sales offer',
        ]);

        $role = Role::findByName('Contractor');
        $role->givePermissionTo([
            'add unit update', 'delete unit update',
        ]);

        // CEO
        $role = Role::findByName('CEO');
        $perms = Permission::all();

        foreach ($perms as $perm)
            $role->givePermissionTo($perm);

        // System Maintenance
        $role = Role::findByName('System Maintenance');
        $perms = Permission::all();

        foreach ($perms as $perm)
            $role->givePermissionTo($perm);
        */

        $permissions = [
            'view unit update',
        ];



        $role = Role::findByName('Contractor');
        $role->givePermissionTo($permissions);
    }
}
