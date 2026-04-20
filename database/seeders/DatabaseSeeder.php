<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Tambahkan import Hash

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        // Buat Akun Admin
        $admin = User::firstOrCreate([
            'email' => 'admin@usn.ac.id'
        ], [
            'name' => 'Administrator',
            'password' => 'password',
        ]);
        $admin->assignRole('admin');

        // Buat Akun Pimpinan
        $pimpinan = User::firstOrCreate([
            'email' => 'pimpinan@usn.ac.id'
        ], [
            'name' => 'Pimpinan',
            'password' => 'password',
        ]);
        $pimpinan->assignRole('pimpinan');
    }
}
