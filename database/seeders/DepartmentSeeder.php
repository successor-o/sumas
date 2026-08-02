<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::truncate();
        
        $faculties = Faculty::all()->keyBy('code');

        $departments = [
            [
                'name' => 'Department of Medicine',
                'code' => 'MED',
                'description' => 'Internal Medicine department',
                'faculty_id' => $faculties['FCS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Surgery',
                'code' => 'SURG',
                'description' => 'Surgical department',
                'faculty_id' => $faculties['FCS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Nursing',
                'code' => 'NURS',
                'description' => 'Nursing department',
                'faculty_id' => $faculties['FHST']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Medical Laboratory Science',
                'code' => 'MLS',
                'description' => 'Medical Laboratory Science department',
                'faculty_id' => $faculties['FHST']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Physiology',
                'code' => 'PHYS',
                'description' => 'Physiology department',
                'faculty_id' => $faculties['FBMS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Anatomy',
                'code' => 'ANAT',
                'description' => 'Anatomy department',
                'faculty_id' => $faculties['FBMS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Biochemistry',
                'code' => 'BCHM',
                'description' => 'Biochemistry department',
                'faculty_id' => $faculties['FBMS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Pharmacology',
                'code' => 'PHRM',
                'description' => 'Pharmacology department',
                'faculty_id' => $faculties['FPS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Community Medicine',
                'code' => 'COMM',
                'description' => 'Community Medicine and Public Health department',
                'faculty_id' => $faculties['FCS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Computer Science',
                'code' => 'CS',
                'description' => 'Computer Science department',
                'faculty_id' => $faculties['FCAS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Microbiology',
                'code' => 'MBIO',
                'description' => 'Microbiology department',
                'faculty_id' => $faculties['FCAS']?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Department of Radiography',
                'code' => 'RAD',
                'description' => 'Radiography department',
                'faculty_id' => $faculties['FHST']?->id,
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
