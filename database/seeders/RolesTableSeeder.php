<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Define roles
        $roles = [
            'admin',
            'user',
            'client',
        ];

        // Create roles if they don't exist
        foreach ($roles as $roleName) {
            $existingRole = Role::where('name', $roleName)->first();

            // Create the role only if it doesn't already exist
            if (!$existingRole) {
                Role::create(['name' => $roleName]);
            }
        }
    }
}
