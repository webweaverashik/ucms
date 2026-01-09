{{-- Partial view for pending students table --}}
<table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table pending-students-datatable"
    id="{{ $tableId }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">SL</th>
            <th class="min-w-200px">Student</th>
            <th class="d-none">Gender (filter)</th>
            <th class="d-none">Class (filter)</th>
            <th>Class</th>
            <th class="d-none">Group (filter)</th>
            <th>Group</th>
            <th class="d-none">Batch (Filter)</th>
            <th>Batch</th>
            <th class="w-300px">Institution</th>
            <th>Guardians</th>
            <th>Mobile<br>(Home)</th>
            <th>Fee (Tk)</th>
            <th>Payment<br>Type</th>
            <th>Admission<br>Date</th>
            <th class="d-none">Branch (filter)</th>
            <th class="min-w-70px not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        @foreach ($students as $student)
            <tr>
                <td class="pe-2">{{ $loop->index + 1 }}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <!--begin:: Avatar -->
                        <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                            <a href="{{ route('students.show', $student->id) }}">
                                <div class="symbol-label">
                                    <img src="{{ $student->photo_url ? asset($student->photo_url) : asset($student->gender == 'male' ? 'img/boy.png' : 'img/girl.png') }}"
                                        alt="{{ $student->name }}" class="w-100" />
                                </div>
                            </a>
                        </div>
                        <!--end::Avatar-->
                        <!--begin::user details-->
                        <div class="d-flex flex-column text-start">
                            <a href="{{ route('students.show', $student->id) }}"
                                class="text-gray-800 text-hover-primary mb-1">{{ $student->name }}
                            </a>
                            <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                        </div>
                        <!--begin::user details-->
                    </div>
                </td>
                <td class="d-none">student_{{ $student->gender }}</td>
                <td class="d-none">{{ $student->class_id }}_{{ $student->class->class_numeral }}_ucms</td>
                <td>{{ $student->class->name }}</td>
                <td class="d-none">ucms_{{ $student->academic_group }}</td>
                <td>
                    @if ($student->academic_group == 'Science')
                        <span class="badge badge-pill badge-info">{{ $student->academic_group }}</span>
                    @elseif ($student->academic_group == 'Commerce')
                        <span class="badge badge-pill badge-success">{{ $student->academic_group }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="d-none">
                    {{ $student->batch_id }}_{{ $student->batch->name }}_{{ $student->branch->branch_name }}
                </td>
                <td>{{ $student->batch->name }}</td>
                <td>{{ $student->institution->name }} (EIIN: {{ $student->institution->eiin_number }})</td>
                <td>
                    @foreach ($student->guardians as $guardian)
                        <a href="#"><span
                                class="badge badge-light-primary rounded-pill text-hover-success fs-7">{{ $guardian->name }},
                                {{ ucfirst($guardian->relationship) }}</span></a><br>
                    @endforeach
                </td>
                <td>
                    {!! $student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('<br>') ?: '-' !!}
                </td>
                <td>
                    @if ($student->payments)
                        {{ $student->payments->tuition_fee }}
                    @endif
                </td>
                <td>
                    @if ($student->payments)
                        {{ ucfirst($student->payments->payment_style) }}-1/{{ $student->payments->due_date }}
                    @endif
                </td>
                <td>{{ $student->created_at->format('d-M-Y') }}</td>
                <td class="d-none">{{ $student->branch_id }}_{{ $student->branch->branch_name }}</td>
                <td>
                    @if ($canApprove || $canEdit || $canDelete)
                        <a href="#" class="btn btn-light btn-active-light-primary btn-sm"
                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                            <i class="ki-outline ki-down fs-5 m-0"></i></a>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4"
                            data-kt-menu="true">
                            @if ($canApprove)
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3 text-hover-success approve-student"
                                        data-student-id="{{ $student->id }}" data-student-name="{{ $student->name }}"
                                        data-student-unique-id="{{ $student->student_unique_id }}">
                                        <i class="bi bi-person-check fs-3 me-2"></i> Approve
                                    </a>
                                </div>
                                <!--end::Menu item-->
                            @endif
                            @if ($canEdit)
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="{{ route('students.edit', $student->id) }}"
                                        class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i>
                                        Edit</a>
                                </div>
                                <!--end::Menu item-->
                            @endif
                            @if ($canDelete)
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link text-hover-danger px-3 delete-student"
                                        data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                        Delete</a>
                                </div>
                                <!--end::Menu item-->
                            @endif
                        </div>
                        <!--end::Menu-->
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
