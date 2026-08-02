<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use App\Models\Lecturer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::truncate();
        
        $departments = Department::all()->keyBy('code');
        $lecturers = Lecturer::all()->keyBy('email');

        $courses = [
            // Medicine Department (FCS)
            [
                'code' => 'MED 101',
                'name' => 'Introduction to Medicine',
                'department' => 'Department of Medicine',
                'department_id' => $departments['MED']?->id,
                'description' => 'Fundamental concepts in medical practice and patient care',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'j.okonkwo@sumas.edu.ng',
            ],
            [
                'code' => 'MED 201',
                'name' => 'Internal Medicine I',
                'department' => 'Department of Medicine',
                'department_id' => $departments['MED']?->id,
                'description' => 'Study of internal diseases and their management',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => 'j.okonkwo@sumas.edu.ng',
            ],
            [
                'code' => 'MED 301',
                'name' => 'Internal Medicine II',
                'department' => 'Department of Medicine',
                'department_id' => $departments['MED']?->id,
                'description' => 'Advanced internal medicine and clinical practice',
                'credit_units' => 6,
                'level' => '300 Level',
                'is_active' => true,
                'lecturer_email' => 'a.bello@sumas.edu.ng',
            ],
            // Surgery Department (FCS)
            [
                'code' => 'SURG 101',
                'name' => 'Introduction to Surgery',
                'department' => 'Department of Surgery',
                'department_id' => $departments['SURG']?->id,
                'description' => 'Basic surgical principles and techniques',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'g.nwosu@sumas.edu.ng',
            ],
            [
                'code' => 'SURG 301',
                'name' => 'Cardiovascular Surgery',
                'department' => 'Department of Surgery',
                'department_id' => $departments['SURG']?->id,
                'description' => 'Specialized surgical procedures for cardiovascular system',
                'credit_units' => 6,
                'level' => '300 Level',
                'is_active' => true,
                'lecturer_email' => 'g.nwosu@sumas.edu.ng',
            ],
            // Nursing Department (FHST)
            [
                'code' => 'NURS 101',
                'name' => 'Fundamentals of Nursing',
                'department' => 'Department of Nursing',
                'department_id' => $departments['NURS']?->id,
                'description' => 'Basic nursing principles and patient care',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'e.okafor@sumas.edu.ng',
            ],
            [
                'code' => 'NURS 201',
                'name' => 'Community Health Nursing',
                'department' => 'Department of Nursing',
                'department_id' => $departments['NURS']?->id,
                'description' => 'Nursing practice in community health settings',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => 'e.okafor@sumas.edu.ng',
            ],
            // Medical Laboratory Science (FHST)
            [
                'code' => 'MLS 101',
                'name' => 'Introduction to Medical Laboratory Science',
                'department' => 'Department of Medical Laboratory Science',
                'department_id' => $departments['MLS']?->id,
                'description' => 'Basic laboratory techniques and diagnostics',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'c.eze@sumas.edu.ng',
            ],
            [
                'code' => 'MLS 201',
                'name' => 'Clinical Chemistry',
                'department' => 'Department of Medical Laboratory Science',
                'department_id' => $departments['MLS']?->id,
                'description' => 'Chemical analysis of body fluids and tissues',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => 'c.eze@sumas.edu.ng',
            ],
            // Physiology (FBMS)
            [
                'code' => 'PHYS 101',
                'name' => 'General Physiology',
                'department' => 'Department of Physiology',
                'department_id' => $departments['PHYS']?->id,
                'description' => 'Basic physiological processes in human body',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'i.musa@sumas.edu.ng',
            ],
            [
                'code' => 'PHYS 301',
                'name' => 'Cardiovascular Physiology',
                'department' => 'Department of Physiology',
                'department_id' => $departments['PHYS']?->id,
                'description' => 'Advanced study of cardiovascular system physiology',
                'credit_units' => 5,
                'level' => '300 Level',
                'is_active' => true,
                'lecturer_email' => 'i.musa@sumas.edu.ng',
            ],
            // Anatomy (FBMS)
            [
                'code' => 'ANAT 101',
                'name' => 'Gross Anatomy',
                'department' => 'Department of Anatomy',
                'department_id' => $departments['ANAT']?->id,
                'description' => 'Structure of human body systems',
                'credit_units' => 5,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'f.ahmed@sumas.edu.ng',
            ],
            [
                'code' => 'ANAT 201',
                'name' => 'Neuroanatomy',
                'department' => 'Department of Anatomy',
                'department_id' => $departments['ANAT']?->id,
                'description' => 'Detailed study of nervous system structure',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => 'f.ahmed@sumas.edu.ng',
            ],
            // Biochemistry (FBMS)
            [
                'code' => 'BCHM 101',
                'name' => 'General Biochemistry',
                'department' => 'Department of Biochemistry',
                'department_id' => $departments['BCHM']?->id,
                'description' => 'Basic biochemical processes in living organisms',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'a.johnson@sumas.edu.ng',
            ],
            [
                'code' => 'BCHM 301',
                'name' => 'Clinical Biochemistry',
                'department' => 'Department of Biochemistry',
                'department_id' => $departments['BCHM']?->id,
                'description' => 'Biochemical basis of diseases and diagnosis',
                'credit_units' => 5,
                'level' => '300 Level',
                'is_active' => true,
                'lecturer_email' => 'a.johnson@sumas.edu.ng',
            ],
            // Pharmacology (FPS)
            [
                'code' => 'PHRM 101',
                'name' => 'Introduction to Pharmacology',
                'department' => 'Department of Pharmacology',
                'department_id' => $departments['PHRM']?->id,
                'description' => 'Basic principles of drug action and therapy',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'n.obi@sumas.edu.ng',
            ],
            [
                'code' => 'PHRM 301',
                'name' => 'Drug Development',
                'department' => 'Department of Pharmacology',
                'department_id' => $departments['PHRM']?->id,
                'description' => 'Principles and processes of drug development',
                'credit_units' => 5,
                'level' => '300 Level',
                'is_active' => true,
                'lecturer_email' => 'n.obi@sumas.edu.ng',
            ],
            // Community Medicine (FCS)
            [
                'code' => 'COMM 101',
                'name' => 'Introduction to Public Health',
                'department' => 'Department of Community Medicine',
                'department_id' => $departments['COMM']?->id,
                'description' => 'Basic public health principles and practices',
                'credit_units' => 3,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => 'c.nnamdi@sumas.edu.ng',
            ],
            [
                'code' => 'COMM 201',
                'name' => 'Epidemiology',
                'department' => 'Department of Community Medicine',
                'department_id' => $departments['COMM']?->id,
                'description' => 'Study of disease distribution and control',
                'credit_units' => 4,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => 'c.nnamdi@sumas.edu.ng',
            ],
            // Computer Science (FCAS)
            [
                'code' => 'CS 101',
                'name' => 'Introduction to Computer Science',
                'department' => 'Department of Computer Science',
                'department_id' => $departments['CS']?->id,
                'description' => 'Fundamental concepts in computing and programming',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => null, // No lecturer assigned yet
            ],
            [
                'code' => 'CS 201',
                'name' => 'Data Structures and Algorithms',
                'department' => 'Department of Computer Science',
                'department_id' => $departments['CS']?->id,
                'description' => 'Advanced programming concepts and algorithm design',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => null,
            ],
            // Microbiology (FCAS)
            [
                'code' => 'MBIO 101',
                'name' => 'General Microbiology',
                'department' => 'Department of Microbiology',
                'department_id' => $departments['MBIO']?->id,
                'description' => 'Study of microorganisms and their effects',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => null,
            ],
            [
                'code' => 'MBIO 201',
                'name' => 'Medical Microbiology',
                'department' => 'Department of Microbiology',
                'department_id' => $departments['MBIO']?->id,
                'description' => 'Microorganisms in medical contexts and disease',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => null,
            ],
            // Radiography (FHST)
            [
                'code' => 'RAD 101',
                'name' => 'Introduction to Radiography',
                'department' => 'Department of Radiography',
                'department_id' => $departments['RAD']?->id,
                'description' => 'Basic principles of medical imaging',
                'credit_units' => 4,
                'level' => '100 Level',
                'is_active' => true,
                'lecturer_email' => null,
            ],
            [
                'code' => 'RAD 201',
                'name' => 'Diagnostic Imaging',
                'department' => 'Department of Radiography',
                'department_id' => $departments['RAD']?->id,
                'description' => 'Advanced diagnostic imaging techniques',
                'credit_units' => 5,
                'level' => '200 Level',
                'is_active' => true,
                'lecturer_email' => null,
            ],
        ];

        foreach ($courses as $courseData) {
            $lecturerEmail = $courseData['lecturer_email'];
            unset($courseData['lecturer_email']);
            
            $course = Course::create($courseData);
            
            // Assign lecturer if email is provided
            if ($lecturerEmail && isset($lecturers[$lecturerEmail])) {
                $course->lecturers()->attach($lecturers[$lecturerEmail]->id);
            }
        }
    }
}
