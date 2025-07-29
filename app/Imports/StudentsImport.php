<?php
namespace App\Imports;

use App\Models\Academic\Subject;
use App\Models\Academic\SubjectTaken;
use App\Models\Payment\Payment;
use App\Models\Student\Guardian;
use App\Models\Student\MobileNumber;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Log the first row to check if data is being read
        Log::info('First row data:', $rows->first()->toArray());

        foreach ($rows as $row) {
            // Skip rows missing required identifiers
            if (empty($row['student_unique_id']) || empty($row['class_id'] || empty($row['branch_id']))) {
                Log::warning('Skipping row due to missing student_unique_id or class_id or branch_id');
                continue;
            }

            // Avoid duplicates
            if (Student::where('student_unique_id', $row['student_unique_id'])->exists()) {
                Log::info('Student already exists: ' . $row['student_unique_id']);
                continue;
            }

            DB::beginTransaction();
            try {
                // Step 1: Insert into students
                $student = Student::create([
                    'branch_id'         => $row['branch_id'],
                    'student_unique_id' => 'G-' . $row['student_unique_id'],
                    'name'              => $row['name'],
                    'date_of_birth'     => $row['date_of_birth'],
                    'gender'            => $row['gender'],
                    'class_id'          => $row['class_id'],
                    'academic_group'    => $row['academic_group'],
                    'shift_id'          => $row['shift_id'],
                    'institution_id'    => $row['institution_id'],
                    'religion'          => $row['religion'] ?? null,
                    'blood_group'       => $row['blood_group'] ?? null,
                    'home_address'      => $row['home_address'] ?? null,
                    'email'             => $row['email'] ?? null,
                    'password'          => Hash::make('12345678'), // Default password
                    'remarks'           => $row['remarks'],
                ]);

                // Step 2: Guardians
                foreach ([1, 2] as $index) {
                    if ($row["guardian_{$index}_name"]) {
                        Guardian::create([
                            'student_id'    => $student->id,
                            'name'          => $row["guardian_{$index}_name"],
                            'mobile_number' => $row["guardian_{$index}_mobile"],
                            'gender'        => $row["guardian_{$index}_gender"],
                            'relationship'  => $row["guardian_{$index}_relationship"],
                        ]);
                    }
                }

                // Step 3: Siblings
                foreach ([1, 2] as $index) {
                    if ($row["sibling_{$index}_name"]) {
                        Sibling::create([
                            'student_id'     => $student->id,
                            'name'           => $row["sibling_{$index}_name"],
                            'age'            => $row["sibling_{$index}_age"],
                            'class'          => $row["sibling_{$index}_class"],
                            'institution_id' => $row["sibling_{$index}_institution_id"] ?? null,
                            'relationship'   => $row["sibling_{$index}_relationship"],
                        ]);
                    }
                }

                // Step 4: Payment
                Payment::create([
                    'student_id'    => $student->id,
                    'payment_style' => $row['payment_style'],
                    'due_date'      => $row['due_date'],
                    'tuition_fee'   => $row['tuition_fee'],
                ]);

                // Step 5: Student Activation
                $activation = StudentActivation::create([
                    'student_id'    => $student->id,
                    'active_status' => $row['activation_status'],
                    'reason'        => $row['inactive_reason'] ?? 'Admitted',
                    'updated_by'    => 1,
                ]);

                $student->update(['student_activation_id' => $activation->id]);

                // Step 6: Mobile Numbers
                $mobileNumbers = [
                    'mobile_home'     => $row['mobile_home'],
                    'mobile_sms'      => $row['mobile_sms'],
                    'mobile_whatsapp' => $row['mobile_whatsapp'],
                ];

                foreach ($mobileNumbers as $key => $number) {
                    if ($number) {
                        MobileNumber::create([
                            'student_id'    => $student->id,
                            'mobile_number' => $number,
                            'number_type'   => str_replace('mobile_', '', $key),
                        ]);
                    }
                }

                // Step 7: Subject Enrollment
                $subjects = Subject::where('class_id', $row['class_id'])
                    ->where('academic_group', $row['academic_group'])
                    ->pluck('id');

                foreach ($subjects as $subjectId) {
                    SubjectTaken::create([
                        'student_id' => $student->id,
                        'subject_id' => $subjectId,
                    ]);
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Failed to import student: {$row['student_unique_id']} - " . $e->getMessage());
            }
        }
    }
}
