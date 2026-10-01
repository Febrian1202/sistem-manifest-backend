<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculties = [
            [
                'code' => 'FTI',
                'name' => 'Fakultas Teknologi Informasi',
                'description' => 'Fakultas yang menaungi keilmuan teknologi informasi, ilmu komputer, dan sistem informasi.',
            ],
            [
                'code' => 'FKIP',
                'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                'description' => 'Fakultas yang menaungi bidang pendidikan dan keguruan.',
            ],
            [
                'code' => 'FISIP',
                'name' => 'Fakultas Ilmu Sosial dan Ilmu Politik',
                'description' => 'Fakultas bidang ilmu sosial, pemerintahan, dan administrasi publik.',
            ],
            [
                'code' => 'FPP',
                'name' => 'Fakultas Pertanian, Perikanan dan Peternakan',
                'description' => 'Fakultas pengembangan riset pertanian, kelautan, dan peternakan.',
            ],
            [
                'code' => 'SAINS-TEK',
                'name' => 'Fakultas Sains dan Teknologi',
                'description' => 'Fakultas sains murni dan rekayasa teknologi.',
            ],
            [
                'code' => 'HUKUM',
                'name' => 'Fakultas Hukum',
                'description' => 'Fakultas ilmu hukum dan perundang-undangan.',
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::firstOrCreate(
                ['code' => $faculty['code']],
                [
                    'name' => $faculty['name'],
                    'description' => $faculty['description'],
                ]
            );
        }
    }
}
