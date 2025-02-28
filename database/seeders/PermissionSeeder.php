<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'add building',
            'edit building',
            'delete building',
            'view building',
            'add unit',
            'edit unit',
            'delete unit',
            'view unit',
            'search unit',
            'approve unit',
            'book unit',
            'hold unit',
            'generate sales offer',
            'approve booking',
            'generate reservation form',
            'approve reservation form',
            'generate spa',
            'sign spa',
            'upload final spa',
            'generate one-time link',
            'approve registration',
            'add unit update',
            'delete unit update',
            'add sales',
            'add crm officer',
            'add crm accountant',
            'add hr admin',
            'add cso',
            'add cfo',
            'add ceo',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
