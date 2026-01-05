<?php
namespace App\Imports;

use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectTaken;
use App\Models\Branch;
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
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    use Importable;

    protected $results;

    public function __construct(&$results)
    {
        $this->results = &$results; // Reference to shared result array
    }

    public function collection(Collection $rows)
    {
        $rowNumber = 2; // Start from 2 if row 1 is the heading

        foreach ($rows as $row) {
            // Skip rows missing required identifiers
            if (empty($row['student_unique_id']) || empty($row['class_id']) || empty($row['branch_id'])) {
                Log::warning("Skipping row {$rowNumber}: Missing student_unique_id, class_id, or branch_id");
                $this->results['skipped'][] = $rowNumber;
                $rowNumber++;
                continue;
            }

            // Avoid duplicates
            if (Student::where('student_unique_id', $row['student_unique_id'])->exists()) {
                Log::info("Row {$rowNumber} skipped: Student already exists - {$row['student_unique_id']}");
                $this->results['skipped'][] = $rowNumber;
                $rowNumber++;
                continue;
            }

            DB::beginTransaction();
            try {
                $branch_prefix = Branch::find($row['branch_id'])->branch_prefix;

                // Step 1: Insert into students
                $student = Student::create([
                    'branch_id'         => $row['branch_id'],
                    'student_unique_id' => $branch_prefix . '-' . $row['student_unique_id'],
                    'name'              => $row['name'],
                    'date_of_birth'     => $row['date_of_birth'],
                    'gender'            => $row['gender'],
                    'class_id'          => $row['class_id'],
                    'academic_group'    => $row['academic_group'],
                    'batch_id'          => $row['batch_id'],
                    'institution_id'    => $row['institution_id'],
                    'home_address'      => $row['home_address'] ?? null,
                    'password'          => Hash::make('12345678'), // Default password
                    'remarks'           => $row['remarks'],
                ]);

                // Step 2: Guardians
                foreach ([1, 2] as $index) {
                    if (! empty($row["guardian_{$index}_name"])) {
                        Guardian::create([
                            'student_id' => $student->id,
                            'name'       => $row["guardian_{$index}_name"],
                            'mobile_number' => $row["guardian_{$index}_mobile"],
                            'gender' => $row["guardian_{$index}_gender"],
                            'relationship' => $row["guardian_{$index}_relationship"],
                        ]);
                    }
                }

                // Step 3: Siblings
                foreach ([1, 2] as $index) {
                    if (! empty($row["sibling_{$index}_name"])) {
                        Sibling::create([
                            'student_id' => $student->id,
                            'name'       => $row["sibling_{$index}_name"],
                            'year' => $row["sibling_{$index}_year"],
                            'class' => $row["sibling_{$index}_class"],
                            'institution_name' => $row["sibling_{$index}_institution_name"] ?? null,
                            'relationship' => $row["sibling_{$index}_relationship"],
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
                    'updated_by'    => auth()->user()->id,
                ]);

                $student->update(['student_activation_id' => $activation->id]);

                // Step 6: Mobile Numbers
                $mobileNumbers = [
                    'mobile_home'     => $row['mobile_home'],
                    'mobile_sms'      => $row['mobile_sms'],
                    'mobile_whatsapp' => $row['mobile_whatsapp'],
                ];

                foreach ($mobileNumbers as $key => $number) {
                    if (! empty($number)) {
                        MobileNumber::create([
                            'student_id'    => $student->id,
                            'mobile_number' => $number,
                            'number_type'   => str_replace('mobile_', '', $key),
                        ]);
                    }
                }

                // Step 7: Subject Enrollment (Compulsory subjects based on class and group)
                $class_numeral      = ClassName::find($row['class_id'])->class_numeral;
                $enrolledSubjectIds = collect(); // Track enrolled subjects to prevent duplicates

                if ($class_numeral >= 9) {
                    // For classes 9 and above: General + specific academic_group
                    $subjects = Subject::where('class_id', $row['class_id'])
                        ->where(function ($query) use ($row) {
                            $query->where('academic_group', $row['academic_group'])->orWhere('academic_group', 'General');
                        })
                        ->pluck('id');
                } else {
                    // For classes below 9: only matching academic_group
                    $subjects = Subject::where('class_id', $row['class_id'])->where('academic_group', $row['academic_group'])->pluck('id');
                }

                // Enroll compulsory/general subjects
                foreach ($subjects as $subjectId) {
                    SubjectTaken::create([
                        'student_id'     => $student->id,
                        'subject_id'     => $subjectId,
                        'is_4th_subject' => false,
                    ]);
                    $enrolledSubjectIds->push($subjectId);
                }

                // Step 8: Optional Subject Enrollment (main_optional_subject and 4th_subject)
                $this->enrollOptionalSubject(
                    $student->id,
                    $row['main_optional_subject'] ?? null,
                    false, // is_4th_subject = 0
                    $enrolledSubjectIds,
                    $rowNumber,
                );

                $this->enrollOptionalSubject(
                    $student->id,
                    $row['4th_subject'] ?? null,
                    true, // is_4th_subject = 1
                    $enrolledSubjectIds,
                    $rowNumber,
                );

                DB::commit();
                $this->results['inserted'][] = $rowNumber;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Row {$rowNumber} failed: " . $e->getMessage());
                $this->results['skipped'][] = $rowNumber;
            }

            $rowNumber++;
        }

        // Clear the cache
        clearUCMSCaches();
    }

    /**
     * Enroll an optional subject for a student with duplicate prevention.
     *
     * @param int $studentId
     * @param mixed $subjectId
     * @param bool $isFourthSubject
     * @param \Illuminate\Support\Collection $enrolledSubjectIds
     * @param int $rowNumber
     * @return void
     */
    protected function enrollOptionalSubject(int $studentId, mixed $subjectId, bool $isFourthSubject, Collection &$enrolledSubjectIds, int $rowNumber): void
    {
        // Skip if subject ID is empty or null
        if (empty($subjectId)) {
            return;
        }

        $subjectId   = (int) $subjectId;
        $subjectType = $isFourthSubject ? '4th_subject' : 'main_optional_subject';

        // Check if subject exists in the database
        if (! Subject::where('id', $subjectId)->exists()) {
            Log::warning("Row {$rowNumber}: {$subjectType} with ID {$subjectId} does not exist, skipping.");
            return;
        }

        // Check if subject is already enrolled (in current import batch)
        if ($enrolledSubjectIds->contains($subjectId)) {
            Log::info("Row {$rowNumber}: {$subjectType} with ID {$subjectId} already enrolled, skipping duplicate.");
            return;
        }

        // Double-check database for existing enrollment (safety net)
        $alreadyAssigned = SubjectTaken::where('student_id', $studentId)->where('subject_id', $subjectId)->exists();

        if ($alreadyAssigned) {
            Log::info("Row {$rowNumber}: {$subjectType} with ID {$subjectId} already exists in database, skipping.");
            return;
        }

        // Create the subject enrollment
        SubjectTaken::create([
            'student_id'     => $studentId,
            'subject_id'     => $subjectId,
            'is_4th_subject' => $isFourthSubject,
        ]);

        // Track the enrolled subject
        $enrolledSubjectIds->push($subjectId);

        Log::info("Row {$rowNumber}: Successfully enrolled {$subjectType} with ID {$subjectId}.");
    }
}
