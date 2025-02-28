<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'CRM Officer',
            'Sales',
            'CSO',
            'Accountant',
            'CFO',
            'CEO',
            'HR Admin',
            'Broker',
            'Contractor',
            'System Maintenance',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
