<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['site_admin', 'org_admin', 'project_admin', 'member'] as $role) {
            Role::findOrCreate($role);
        }
    }
}
