<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create permissions
        Permission::firstOrCreate(['name' => 'access admin panel', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage computers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage licenses', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view reports', 'guard_name' => 'web']);

        // 2. Create roles and assign permissions
        
        // Admin: Full access
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        // Pimpinan: Read-only access
        $pimpinanRole = Role::firstOrCreate(['name' => 'pimpinan', 'guard_name' => 'web']);
        $pimpinanRole->givePermissionTo([
            'access admin panel',
            'view reports',
        ]);

        // 3. Assign admin role to existing users (optional, but safe for development)
        $users = User::all();
        foreach ($users as $user) {
            $user->assignRole($adminRole);
        }
    }
}
