<?php

namespace Database\Seeders\Teacher;

use App\Models\Teacher\Teacher;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // Creates 10 teachers with random data
        Teacher::factory()->count(15)->create();
    }
}
