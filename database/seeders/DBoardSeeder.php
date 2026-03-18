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
        $perms = [
            Permission::findOrCreate('view broker bookings'),
            Permission::findOrCreate('view broker commissions')
        ];

        $roles = [
            'CEO',
            'System Maintenance',
            'CFO',
        ];

        foreach ($roles as $roleName) {
            $role = Role::findOrCreate($roleName);

            foreach ($perms as $perm) {
                $role->givePermissionTo($perm);
            }
        }
    }
}
