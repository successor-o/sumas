<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicLevel::truncate();

        $levels = [
            ['name' => '100 Level', 'code' => '100', 'sort_order' => 10, 'description' => 'First year — Foundation courses', 'is_active' => true],
            ['name' => '200 Level', 'code' => '200', 'sort_order' => 20, 'description' => 'Second year — Intermediate courses', 'is_active' => true],
            ['name' => '300 Level', 'code' => '300', 'sort_order' => 30, 'description' => 'Third year — Advanced intermediate courses', 'is_active' => true],
            ['name' => '400 Level', 'code' => '400', 'sort_order' => 40, 'description' => 'Fourth year — Professional courses', 'is_active' => true],
            ['name' => '500 Level', 'code' => '500', 'sort_order' => 50, 'description' => 'Fifth year — Specialised / research courses', 'is_active' => true],
            ['name' => '600 Level', 'code' => '600', 'sort_order' => 60, 'description' => 'Sixth year — Clinical rotations / final year', 'is_active' => true],
        ];

        foreach ($levels as $level) {
            AcademicLevel::create($level);
        }
    }
}
