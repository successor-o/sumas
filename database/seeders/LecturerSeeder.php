<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LecturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lecturer::truncate();
        
        $departments = Department::all()->keyBy('code');

        $lecturers = [
            [
                'name' => 'Dr. John Okonkwo',
                'email' => 'j.okonkwo@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348012345678',
                'department' => 'Department of Medicine',
                'department_id' => $departments['MED']?->id,
                'bio' => 'Senior Lecturer in Internal Medicine with 15 years experience',
                'is_active' => true,
            ],
            [
                'name' => 'Prof. Grace Nwosu',
                'email' => 'g.nwosu@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348023456789',
                'department' => 'Department of Surgery',
                'department_id' => $departments['SURG']?->id,
                'bio' => 'Professor of Surgery specializing in cardiovascular surgery',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Emeka Okafor',
                'email' => 'e.okafor@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348034567890',
                'department' => 'Department of Nursing',
                'department_id' => $departments['NURS']?->id,
                'bio' => 'Lecturer in Nursing with focus on community health',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Chioma Eze',
                'email' => 'c.eze@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348045678901',
                'department' => 'Department of Medical Laboratory Science',
                'department_id' => $departments['MLS']?->id,
                'bio' => 'Senior Lecturer in Medical Laboratory Science',
                'is_active' => true,
            ],
            [
                'name' => 'Prof. Ibrahim Musa',
                'email' => 'i.musa@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348056789012',
                'department' => 'Department of Physiology',
                'department_id' => $departments['PHYS']?->id,
                'bio' => 'Professor of Physiology with research in cardiovascular physiology',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Fatima Ahmed',
                'email' => 'f.ahmed@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348067890123',
                'department' => 'Department of Anatomy',
                'department_id' => $departments['ANAT']?->id,
                'bio' => 'Lecturer in Anatomy with focus on neuroanatomy',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Adebayo Johnson',
                'email' => 'a.johnson@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348078901234',
                'department' => 'Department of Biochemistry',
                'department_id' => $departments['BCHM']?->id,
                'bio' => 'Senior Lecturer in Biochemistry',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Ngozi Obi',
                'email' => 'n.obi@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348089012345',
                'department' => 'Department of Pharmacology',
                'department_id' => $departments['PHRM']?->id,
                'bio' => 'Lecturer in Pharmacology with research in drug development',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Chukwuma Nnamdi',
                'email' => 'c.nnamdi@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348090123456',
                'department' => 'Department of Community Medicine',
                'department_id' => $departments['COMM']?->id,
                'bio' => 'Senior Lecturer in Community Medicine and Public Health',
                'is_active' => true,
            ],
            [
                'name' => 'Dr. Aisha Bello',
                'email' => 'a.bello@sumas.edu.ng',
                'password' => bcrypt('password123'),
                'phone' => '+2348101234567',
                'department' => 'Department of Medicine',
                'department_id' => $departments['MED']?->id,
                'bio' => 'Lecturer in Internal Medicine specializing in infectious diseases',
                'is_active' => true,
            ],
        ];

        foreach ($lecturers as $lecturer) {
            Lecturer::create($lecturer);
        }
    }
}
