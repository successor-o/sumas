<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'Faculty of Clinical Sciences',
                'code' => 'FCS',
                'description' => 'Faculty covering medical and surgical specialties',
                'is_active' => true,
            ],
            [
                'name' => 'Faculty of Basic Medical Sciences',
                'code' => 'FBMS',
                'description' => 'Faculty covering Anatomy, Physiology, Biochemistry and other basic sciences',
                'is_active' => true,
            ],
            [
                'name' => 'Faculty of Health Sciences & Technology',
                'code' => 'FHST',
                'description' => 'Faculty covering Nursing, Medical Lab Science, Radiography and other health sciences',
                'is_active' => true,
            ],
            [
                'name' => 'Faculty of Pharmaceutical Sciences',
                'code' => 'FPS',
                'description' => 'Faculty covering Pharmacy and Pharmacology',
                'is_active' => true,
            ],
            [
                'name' => 'Faculty of Computing & Applied Sciences',
                'code' => 'FCAS',
                'description' => 'Faculty covering Computer Science, Biochemistry, Microbiology and other applied sciences',
                'is_active' => true,
            ],
        ];

        foreach ($faculties as $faculty) {
            Faculty::query()->create($faculty);
        }
    }
}
