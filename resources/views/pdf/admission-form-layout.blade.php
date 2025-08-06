<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $student->student_unique_id }} - Admission Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        @import url('https://fonts.cdnfonts.com/css/solaimanlipi');

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Open Sans', sans-serif;
        }

        /* Custom dotted underline for input lines */
        .dotted-underline {
            border-bottom: 1px dotted black;
        }

        /* Smaller font for some text */
        .text-7px {
            font-size: 7px;
        }

        /* Very small text for notes */
        .text-6px {
            font-size: 6px;
        }

        /* Custom border for the outer frame */
        .outer-frame {
            border: 1px solid black;
            position: relative;
        }

        /* Corner decorations */
        .corner {
            width: 20px;
            height: 20px;
            border: 2px solid black;
            position: absolute;
        }

        .corner.tl {
            top: 0;
            left: 0;
            border-right: none;
            border-bottom: none;
        }

        .corner.tr {
            top: 0;
            right: 0;
            border-left: none;
            border-bottom: none;
        }

        .corner.bl {
            bottom: 0;
            left: 0;
            border-right: none;
            border-top: none;
        }

        .corner.br {
            bottom: 0;
            right: 0;
            border-left: none;
            border-top: none;
        }

        /* Smaller checkboxes */
        input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: black;
        }

        /* Smaller radio buttons if needed */
        input[type="radio"] {
            width: 14px;
            height: 14px;
        }

        /* Remove default input number spin */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body class="p-1">
    <div class="max-w-[1000px] h-full mx-auto outer-frame p-4 relative">
        <!-- Corner decorations -->
        <div class="corner tl"></div>
        <div class="corner tr"></div>
        <div class="corner bl"></div>
        <div class="corner br"></div>

        <div class="flex justify-between items-center mb-2">
            <div class="flex flex-column items-center space-x-2">
                <div class="w-20 h-20 flex items-center justify-center text-2xl font-bold italic">
                    <img src="{{ asset('img/uc-blue-logo.png') }}" alt="">
                </div>
            </div>

            <div>
                <h1 class="text-6xl font-black text-center mb-0" style="font-family: 'SolaimanLipi', sans-serif;">ইউনিক
                    কোচিং
                </h1>
                <p class="text-center text-[11px] mb-1">
                    {{ $student->branch->address }}, Phone: {{ $student->branch->phone_number }}<br />
                    {{ $student->branch->branch_name }} Branch
                </p>
            </div>
            <div class="w-24 h-24 rounded-full overflow-hidden">
                <img src="{{ asset($student->photo_url ?? ($student->gender == 'male' ? 'img/male.png' : 'img/female.png')) }}"
                    alt="" class="w-full h-full rounded-full object-cover">
            </div>
        </div>



        <div class="text-center mb-4">
            <button class="border border-black rounded-full px-4 py-1 text-[15px] font-semibold">ADMISSION FORM</button>
        </div>

        <div class="inline-block mb-2 text-[15px] font-bold">&#9884; Student Information</div>

        <form class="text-[15px]">
            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap">Full Name</label>
                <span class="mr-1 shrink-0">:</span>
                <div class="flex-1 min-w-0 border-b border-dotted border-black dotted-underline ml-2">
                    {{ $student->name }}
                </div>
            </div>


            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">Home Address</label>
                <span class="mr-1 shrink-0">:</span>
                <div class="flex-1 min-w-0 border-b border-dotted border-black dotted-underline ml-2">
                    {{ $student->home_address }}
                </div>
            </div>


            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">Phone (Home)</label>
                <span class="shrink-0 mr-1">:</span>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[200px] mx-2">
                    {{ $student->mobileNumbers->where('number_type', 'home')->first()->mobile_number }}
                </div>

                <span class="shrink-0 mx-2 font-normal whitespace-nowrap">(Whatsapp)</span>
                <div class="flex-1 min-w-0 border-b border-dotted border-black dotted-underline">
                    {{ optional($student->mobileNumbers->where('number_type', 'whatsapp')->first())->mobile_number ?? '.' }}
                </div>
            </div>


            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">Phone (SMS)</label>
                <span class="shrink-0 mr-1">:</span>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[200px] mx-2">
                    {{ $student->mobileNumbers->where('number_type', 'sms')->first()->mobile_number }}
                </div>

                <span class="shrink-0 ml-2 font-normal whitespace-nowrap">Religion :</span>

                <label class="ml-2 shrink-0 flex items-center font-normal whitespace-nowrap">
                    <input type="checkbox" class="mr-1" {{ $student->religion == 'Islam' ? 'checked' : '' }} /> Islam
                </label>
                <label class="ml-2 shrink-0 flex items-center font-normal whitespace-nowrap">
                    <input type="checkbox" class="mr-1" {{ $student->religion == 'Hindu' ? 'checked' : '' }} /> Hindu
                </label>
                <label class="ml-2 shrink-0 flex items-center font-normal whitespace-nowrap">
                    <input type="checkbox" class="mr-1" {{ $student->religion == 'Others' ? 'checked' : '' }} />
                    Others
                </label>
            </div>


            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">Class</label>
                <span class="shrink-0 mr-1">:</span>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline w-[75px] mx-2">
                    {{ $student->class->class_numeral }}
                </div>

                <label class="shrink-0 font-normal mr-2 whitespace-nowrap">Blood Group :</label>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[50px] mr-2">
                    {{ $student->blood_group ?? '.' }}
                </div>

                <label class="shrink-0 font-normal mr-2 whitespace-nowrap">DoB :</label>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[70px] mr-2">
                    {{ $student->date_of_birth->format('d/m/Y') ?? '.'}}
                </div>

                <label class="shrink-0 ml-2 font-normal whitespace-nowrap">Gender :</label>
                <label class="ml-2 shrink-0 flex items-center font-normal whitespace-nowrap">
                    <input type="checkbox" class="mr-1" {{ $student->gender == 'male' ? 'checked' : '' }} /> Male
                </label>
                <label class="ml-2 shrink-0 flex items-center font-normal whitespace-nowrap">
                    <input type="checkbox" class="mr-1" {{ $student->gender == 'female' ? 'checked' : '' }} /> Female
                </label>
            </div>


            <div class="mb-2 flex items-start print:flex-nowrap">
                <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">School/College</label>
                <span class="shrink-0 mr-1">:</span>
                <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[200px] mx-2">
                    {{ $student->institution->name }} (EIIN: {{ $student->institution->eiin_number }})
                </div>

                <label class="shrink-0 font-normal mr-2 whitespace-nowrap">Group :</label>
                <div class="flex-1 min-w-0 border-b border-dotted border-black dotted-underline">
                    {{ $student->academic_group }}
                </div>
            </div>


            @php
                $guardians = $student->guardians;
            @endphp

            @foreach ($guardians as $index => $guardian)
                <div class="mb-1 flex items-start print:flex-nowrap">
                    <label class="w-[125px] shrink-0 whitespace-nowrap font-normal">
                        {{ ucfirst($guardian->relationship) }}'s Name
                    </label>
                    <span class="shrink-0 mr-1">:</span>
                    <div class="shrink-0 border-b border-dotted border-black dotted-underline min-w-[400px] mx-2">
                        {{ $guardian->name }}
                    </div>

                    <label class="shrink-0 font-normal mr-2 whitespace-nowrap">Phone :</label>
                    <div class="flex-1 min-w-0 border-b border-dotted border-black dotted-underline">
                        {{ $guardian->mobile_number }}
                    </div>
                </div>
            @endforeach


            <div class="mt-2 mb-1 font-bold text-[15px]">&#9884; About Brothers & Sisters (Full Description of their
                Education):</div>

            <table class="w-full border border-black text-[14px] mb-2 table-fixed">
                <thead>
                    <tr>
                        <th class="border border-black w-[25%]">Name</th>
                        <th class="border border-black w-[10%]">Class/Age</th>
                        <th class="border border-black w-[10%]">Year</th>
                        <th class="border border-black w-[30%]">School/College</th>
                        <th class="border border-black w-[15%]">Relation</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($student->siblings->count() == 0)
                        <tr>
                            <td class="border border-black h-8"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                        </tr>
                        <tr>
                            <td class="border border-black h-8"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                        </tr>
                    @elseif ($student->siblings->count() == 1)
                        @foreach ($student->siblings as $sibling)
                            <tr class="text-center">
                                <td class="border border-black h-8">{{ $sibling->name }}</td>
                                <td class="border border-black">{{ $sibling->class }}</td>
                                <td class="border border-black">{{ $sibling->year }}</td>
                                <td class="border border-black">{{ $sibling->institution_name }}</td>
                                <td class="border border-black">{{ ucfirst($sibling->relationship) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="border border-black h-8"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                            <td class="border border-black"></td>
                        </tr>
                    @else
                        @foreach ($student->siblings as $sibling)
                            <tr class="text-center">
                                <td class="border border-black h-8">{{ $sibling->name }}</td>
                                <td class="border border-black">{{ $sibling->class }}</td>
                                <td class="border border-black">{{ $sibling->year }}</td>
                                <td class="border border-black">{{ $sibling->institution_name }}</td>
                                <td class="border border-black">{{ ucfirst($sibling->relationship) }}</td>
                            </tr>
                        @endforeach
                    @endif

                </tbody>
            </table>

            <div class="inline-block mb-2 text-[15px] font-bold">&#9884; Enrolled Subjects</div>

            @php
                $takenSubjectIds = $student->subjectsTaken->pluck('subject_id')->toArray();

                // Desired group order
                $groupOrder = ['General', 'Science', 'Commerce'];

                // Sort and group subjects by academic_group
                $groupedSubjects = collect($student->class->subjects)
                    ->sortBy(function ($subject) use ($groupOrder) {
                        return array_search($subject->academic_group, $groupOrder) !== false
                            ? array_search($subject->academic_group, $groupOrder)
                            : count($groupOrder); // put unmatched groups at the end
                    })
                    ->groupBy('academic_group')
                    ->filter(function ($subjects) use ($takenSubjectIds) {
                        // Keep only groups that have at least one subject taken by the student
                        return $subjects->pluck('id')->intersect($takenSubjectIds)->isNotEmpty();
                    });
            @endphp

            <div class="mb-1 space-y-1 text-[13px] font-normal">
                @foreach ($groupedSubjects as $group => $subjects)
                    <div class="mb-2">
                        <div class="font-semibold text-gray-600 mb-1">{{ $group ?? 'Others' }}:</div>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach ($subjects as $subject)
                                <label class="flex items-center space-x-1">
                                    <input type="checkbox" class="form-checkbox" name="subjects[]"
                                        value="{{ $subject->id }}"
                                        {{ in_array($subject->id, $takenSubjectIds) ? 'checked' : '' }} />
                                    <span>{{ $subject->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>



            <div class="flex justify-around text-[12px] font-normal mt-10 mb-2">
                <span class="border-t border-solid border-black min-w-[80px]">Date & Signature of Guardian</span>
                <span class="border-t border-solid border-black min-w-[80px]">Date & Signature of Student</span>
            </div>

            <div
                class="w-100 text-center pb-1 mb-2 mx-auto text-[10px] border-b border-dotted border-[#333] dotted-underline">
                # N.B.: If you change any kind of information above, please inform the office as soon as possible.</div>

            <div class="absolute bottom-0 mb-1">
                <div class="border border-black rounded px-2 py-1 w-max text-[15px] font-bold mb-2 mx-auto">Only for
                    Admin</div>

                <div class="mb-2 flex flex-wrap items-center text-[15px] font-normal">
                    <label class="w-[125px]">Student ID</label>:
                    <div
                        class="flex-1 border-b border-dotted border-black dotted-underline min-w-[200px] font-bold ml-2">
                        {{ $student->student_unique_id }}</div>

                    <label class="ml-4 mr-2">Date of Admission :</label>
                    <div class="flex-1 border-b border-dotted border-black dotted-underline min-w-[200px] font-bold">
                        {{ $student->created_at->format('d/m/Y') }}</div>
                </div>

                <div class="mb-2 flex flex-wrap items-center text-[15px] font-normal">
                    <label class="w-[125px]">Salary/Package</label>:
                    <div
                        class="flex-1 border-b border-dotted border-black dotted-underline min-w-[50px] font-bold mx-2">
                        {{ intval($student->payments->tuition_fee) }}</div>
                    <span>Taka.</span>

                    <label class="ml-4 mr-2">Way of Payment :</label>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->due_date == 7) checked @endif>1 to 7</label>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->due_date == 10) checked @endif />1 to 10</label>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->due_date == 15) checked @endif />1 to 15</label>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->due_date == 30) checked @endif />1 to 30</label>
                </div>

                <div class="mb-2 flex flex-wrap items-center text-[15px] font-normal">
                    <label class="w-[125px]">Shift</label>:
                    @foreach ($student->branch->shifts as $shift)
                        <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                                value="{{ $shift->id }}"
                                @if ($shift->id == $student->shift_id) checked @endif />{{ $shift->name }}</label>
                    @endforeach

                    <button type="button"
                        class="ml-auto border border-black rounded px-2 py-0.5 text-[15px] font-semibold">Payment
                        Type:</button>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->payment_style == 'current') checked @endif />Current</label>
                    <label class="ml-2 flex items-center"><input type="checkbox" class="mr-1"
                            @if ($student->payments->payment_style == 'due') checked @endif />Due</label>
                </div>

                <div class="mb-2 flex flex-wrap items-center text-[15px] font-normal">
                    <label class="w-[125px]">Reference</label>:
                    <div class="flex-1 border-b border-dotted border-black dotted-underline min-w-[200px] ml-2">
                        @if ($student->reference && $student->reference->referer)
                            @php
                                $referer = $student->reference->referer;
                            @endphp

                            @if ($referer instanceof \App\Models\Student\Student)
                                {{ $referer->name }} ({{ $referer->student_unique_id }})
                            @else
                                {{ $referer->name }}
                            @endif
                        @else
                            .
                        @endif
                    </div>
                    <label class="font-normal mx-2">Others Note :</label>
                    <div class="flex-1 border-b border-dotted border-black dotted-underline min-w-[200px]">
                        {{ $student->remarks ?? '-' }}</div>
                </div>

                <div class="flex justify-between text-[12px] font-normal mt-10">
                    <span class="border-t border-solid border-black min-w-[80px]">Date & Signature of Admin</span>
                    <span class="border-t border-solid border-black min-w-[80px]">Information Update</span>
                </div>
            </div>
        </form>
    </div>
</body>

</html>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500); // Delay to allow layout and images to load
    };

    window.onafterprint = function() {
        window.close();
    };
</script>
