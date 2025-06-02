<?php
namespace Database\Seeders\Academic;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creates 10 institutions, with different types (school/college)
        // Institution::factory()->count(10)->create();

        $institutions = [
            ['name' => 'Abdul Aziz School and College', 'type' => 'college'],
            ['name' => 'Abudharr Ghifari College', 'type' => 'college'],
            ['name' => 'Ali Ahmed High School and College', 'type' => 'college'],
            ['name' => 'BAF Saheen College', 'type' => 'college'],
            ['name' => 'Banasree Ideal School', 'type' => 'school'],
            ['name' => 'Banasree Model School', 'type' => 'school'],
            ['name' => 'Basabo Model School', 'type' => 'school'],
            ['name' => 'Begum Badrunnesa Government Girls\' College', 'type' => 'college'],
            ['name' => 'BMARPC', 'type' => 'college'],
            ['name' => 'Boarchar P.B. School', 'type' => 'school'],
            ['name' => 'Chargas NIB High School', 'type' => 'school'],
            ['name' => 'Dakhin Banasree Model High School', 'type' => 'school'],
            ['name' => 'Dhaka City College', 'type' => 'college'],
            ['name' => 'Dhaka College', 'type' => 'college'],
            ['name' => 'Dhaka Eastern School', 'type' => 'school'],
            ['name' => 'Dhaka Imperial College', 'type' => 'college'],
            ['name' => 'Faizur Rahman Ideal Institute', 'type' => 'college'],
            ['name' => 'Goran Adarsho High School', 'type' => 'school'],
            ['name' => 'Govt. Safar Ali College', 'type' => 'college'],
            ['name' => 'Govt. Shaheed Suhrawardy College', 'type' => 'college'],
            ['name' => 'Grame lekha pora kore', 'type' => 'school'],
            ['name' => 'Haider Ali School and College', 'type' => 'college'],
            ['name' => 'Ideal College, Dhanmondi', 'type' => 'college'],
            ['name' => 'Ideal Muslim Boys & Girls School', 'type' => 'school'],
            ['name' => 'Ideal School and College, Motijheel', 'type' => 'college'],
            ['name' => 'Imperial College', 'type' => 'college'],
            ['name' => 'Kabi Nazrul Government College', 'type' => 'college'],
            ['name' => 'Kadamtola Purbo Bashabo School and College', 'type' => 'college'],
            ['name' => 'Khilgaon Government College', 'type' => 'college'],
            ['name' => 'Khilgaon Government Colony School and College', 'type' => 'college'],
            ['name' => 'Khilgaon Government High School', 'type' => 'school'],
            ['name' => 'Khilgaon Girls\' High School and College', 'type' => 'college'],
            ['name' => 'Khilgaon Model High School', 'type' => 'school'],
            ['name' => 'Khilgaon Shahjahanpur Railway Government High School', 'type' => 'school'],
            ['name' => 'Lalbag Govt Model College', 'type' => 'college'],
            ['name' => 'Mirza Abbas Mohila College', 'type' => 'college'],
            ['name' => 'Motijheel Government Boys High School', 'type' => 'school'],
            ['name' => 'Motijheel Government Girls High School', 'type' => 'school'],
            ['name' => 'Motijheel Ideal College', 'type' => 'college'],
            ['name' => 'Motijheel Model School', 'type' => 'school'],
            ['name' => 'Mugdha Ideal School', 'type' => 'school'],
            ['name' => 'National Ideal School and College', 'type' => 'college'],
            ['name' => 'Nazmul Haque Kamil Madrasa', 'type' => 'school'],
            ['name' => 'Nijamuddin High School', 'type' => 'school'],
            ['name' => 'Online Kinder Garden', 'type' => 'school'],
            ['name' => 'Purba Rampura High School', 'type' => 'school'],
            ['name' => 'Quality Education School and College', 'type' => 'college'],
            ['name' => 'Rajarbagh Police Lines School & College', 'type' => 'college'],
            ['name' => 'Sabujbagh Government College', 'type' => 'college'],
            ['name' => 'Shaheed Suhrawardy College', 'type' => 'college'],
            ['name' => 'Shajanpur Railway Government School', 'type' => 'school'],
            ['name' => 'Shantipur High School', 'type' => 'school'],
            ['name' => 'Shiddheswari Girls\' College', 'type' => 'college'],
            ['name' => 'Shobujbagh Government High School', 'type' => 'school'],
            ['name' => 'South Banasree Model High School and College', 'type' => 'college'],
            ['name' => 'South Point School and College', 'type' => 'college'],
            ['name' => 'Tamirul Millat Kamil Madrasha', 'type' => 'school'],
            ['name' => 'Tejgaon College', 'type' => 'college'],
            ['name' => 'Udoyon School & College', 'type' => 'college'],
            ['name' => 'Viqarunnisa Noon School & College', 'type' => 'college'],
        ];

        foreach ($institutions as $institution) {
            DB::table('institutions')->insert([
                'name'        => $institution['name'],
                'eiin_number' => $this->generateRandomEiin(),
                'type'        => $institution['type'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /**
     * Generate a random 6-digit EIIN number.
     */
    private function generateRandomEiin(): string
    {
        return (string) rand(100000, 999999);
    }
}
