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
            ['name' => 'Birshreshtha Munshi Abdur Rouf Public College', 'type' => 'college'],
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
            ['name' => 'Meradia High School ', 'type' => 'school'],
            ['name' => 'Motijheel Government Boys High School', 'type' => 'school'],
            ['name' => 'Motijheel Government Girls High School', 'type' => 'school'],
            ['name' => 'Motijheel Model School & College', 'type' => 'school'],
            ['name' => 'Mugdha Ideal School', 'type' => 'school'],
            ['name' => 'National Ideal School and College', 'type' => 'college'],
            ['name' => 'Nazmul Haque Kamil Madrasa', 'type' => 'school'],
            ['name' => 'Nijamuddin High School', 'type' => 'school'],
            ['name' => 'Online Kinder Garden', 'type' => 'school'],
            ['name' => 'Purba Rampura High School', 'type' => 'school'],
            ['name' => 'Quality Education School and College', 'type' => 'college'],
            ['name' => 'Rajarbagh Police Lines School & College', 'type' => 'college'],
            ['name' => 'Rajuk Uttara Model College', 'type' => 'college'],
            ['name' => 'Sabujbag Government College', 'type' => 'college'],
            ['name' => 'Shaheed Suhrawardy College', 'type' => 'college'],
            ['name' => 'Shajanpur Railway Government School', 'type' => 'school'],
            ['name' => 'Shantipur High School', 'type' => 'school'],
            ['name' => 'Rampura Ekramunnesa College', 'type' => 'college'],
            ['name' => 'Sabujbag Government High School', 'type' => 'school'],
            ['name' => 'South Banasree Model High School and College', 'type' => 'college'],
            ['name' => 'South Point School and College', 'type' => 'college'],
            ['name' => 'Tamirul Millat Kamil Madrasha', 'type' => 'school'],
            ['name' => 'Tejgaon College', 'type' => 'college'],
            ['name' => 'Udoyon School & College', 'type' => 'college'],
            ['name' => 'Viqarunnisa Noon School & College', 'type' => 'college'],
            ['name' => 'Ilma Nobobi Madrasa', 'type' => 'school'],
            ['name' => 'Frime School and College', 'type' => 'school'],
            ['name' => 'Willes Littel Flower School', 'type' => 'school'],
            ['name' => 'NO SCHOOL', 'type' => 'school'],
            ['name' => 'Railway Hafiziya Sunniya Alia Madrasha', 'type' => 'school'],
            ['name' => 'NO COLLEGE', 'type' => 'college'],
            ['name' => 'Shonaimuri Girls High School', 'type' => 'school'],
            ['name' => 'Khilgaon Model College', 'type' => 'college'],
            ['name' => 'Ali Ahmed High School', 'type' => 'school'],
            ['name' => 'Quality Education School', 'type' => 'school'],
            ['name' => 'Quality Education College', 'type' => 'college'],
            ['name' => 'Khilgaon Girls School', 'type' => 'school'],
            ['name' => 'Khilgaon Girls College', 'type' => 'college'],
            ['name' => 'Siddheswari Girls College', 'type' => 'college'],
            ['name' => 'Siddheswari Degree College', 'type' => 'college'],
            ['name' => 'Faridgonj Girls Pilot High School', 'type' => 'school'],
            ['name' => 'Siddheswari Girls High School', 'type' => 'school'],
            ['name' => 'Faizur Rahman School', 'type' => 'school'],
            ['name' => 'National Ideal School', 'type' => 'school'],
            ['name' => 'Prime School & College', 'type' => 'school'],
            ['name' => 'Khilgaon Ideal school', 'type' => 'school'],
            ['name' => 'North Point School', 'type' => 'school'],
            ['name' => 'South Point School', 'type' => 'school'],
            ['name' => 'Chetona Model Academy', 'type' => 'school'],
            ['name' => 'Motijheel Ideal School', 'type' => 'school'],
            ['name' => 'Rajarbagh Police Lines School', 'type' => 'school'],
            ['name' => 'Viqarunnisa Noon School', 'type' => 'school'],
            ['name' => 'Kadamtola Purbobasabo School', 'type' => 'school'],
            ['name' => 'Dhaka Ideal School', 'type' => 'school'],
            ['name' => 'Tamirul Milat Kamil Madrasa', 'type' => 'college'],
            ['name' => 'Wills Little Flower College', 'type' => 'college'],
            ['name' => 'Future Commerce College', 'type' => 'college'],
            ['name' => 'Notre Dame College', 'type' => 'college'],
            ['name' => 'Habibullah Bahar university College', 'type' => 'college'],
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
