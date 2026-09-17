<?php

namespace Database\Seeders;

use App\Models\Laboratory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([RoleAndPermissionSeeder::class]);

        // Buat Sample Laboratories
        $labKomputer1 = Laboratory::firstOrCreate(
            ['code' => 'LAB-KOM1'],
            [
                'name' => 'Laboratorium Komputer 1',
                'building' => 'Gedung A',
                'floor' => '2',
                'description' => 'Lab komputer umum lantai 2',
            ]
        );

        $labKomputer2 = Laboratory::firstOrCreate(
            ['code' => 'LAB-KOM2'],
            [
                'name' => 'Laboratorium Komputer 2',
                'building' => 'Gedung A',
                'floor' => '3',
                'description' => 'Lab komputer umum lantai 3',
            ]
        );

        $labJaringan = Laboratory::firstOrCreate(
            ['code' => 'LAB-JRG'],
            [
                'name' => 'Laboratorium Jaringan',
                'building' => 'Gedung B',
                'floor' => '1',
                'description' => 'Lab praktikum jaringan komputer',
            ]
        );

        // Buat Akun Admin
        $admin = User::firstOrCreate(
            [
                'email' => 'admin@usn.ac.id',
            ],
            [
                'name' => 'Administrator',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
            ],
        );
        $admin->assignRole('admin');

        // Buat Akun Pimpinan (Dekan/Kaprodi)
        $pimpinan = User::firstOrCreate(
            [
                'email' => 'pimpinan@usn.ac.id',
            ],
            [
                'name' => 'Dekan FTI',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
            ],
        );
        $pimpinan->assignRole('pimpinan');

        // Buat Akun Kepala Lab 1
        $kepalaLab1 = User::firstOrCreate(
            [
                'email' => 'kepalalab@usn.ac.id',
            ],
            [
                'name' => 'Kepala Lab Komputer',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
                'laboratory_id' => $labKomputer1->id,
            ],
        );
        $kepalaLab1->assignRole('kepala_lab');

        // Buat Akun Kepala Lab 2
        $kepalaLab2 = User::firstOrCreate(
            [
                'email' => 'pjlab2@usn.ac.id',
            ],
            [
                'name' => 'Koordinator Lab Jaringan',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
                'laboratory_id' => $labJaringan->id,
            ],
        );
        $kepalaLab2->assignRole('kepala_lab');
    }
}
