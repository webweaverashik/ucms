<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class ReferenceController extends Controller
{
    public function getTeachers(): JsonResponse
    {
        $teachers = Teacher::select(['id', 'name'])->get(); // Adjust fields as needed
        return response()->json($teachers);
    }

    public function getStudents(): JsonResponse
    {
        $students = Student::select(['id', 'name', 'student_unique_id'])->get(); // Adjust fields as needed
        return response()->json($students);
    }
}
