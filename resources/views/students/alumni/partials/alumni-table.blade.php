{{-- Partial view for alumni students table --}}
<table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table alumni-students-datatable"
    id="{{ $tableId }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">SL</th>
            <th class="min-w-200px">Student</th>
            <th class="d-none">Gender (filter)</th>
            <th class="d-none">Active/Inactive (filter)</th>
            <th class="d-none">Class (filter)</th>
            <th>Class</th>
            <th class="d-none">Batch (Filter)</th>
            <th>Batch</th>
            <th class="w-300px">Institution</th>
            <th>Guardians</th>
            <th>Mobile<br>(Home)</th>
            <th>Fee (Tk)</th>
            <th>Payment<br>Type</th>
            <th class="d-none">Branch (filter)</th>
            @if ($showBranchColumn)
                <th>Branch</th>
            @endif
            <th class="min-w-70px not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        @foreach ($students as $student)
            <tr>
                <td class="pe-2">{{ $loop->index + 1 }}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <!--begin::user details-->
                        <div class="d-flex flex-column text-start">
                            <a href="{{ route('students.show', $student->id) }}"
                                class="@if ($student->studentActivation->active_status == 'inactive') text-danger @else text-gray-800 text-hover-primary @endif mb-1"
                                @if ($student->studentActivation->active_status == 'inactive') title="Inactive Student" data-bs-toggle="tooltip" data-bs-placement="top" @endif>{{ $student->name }}
                            </a>
                            <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                        </div>
                        <!--begin::user details-->
                    </div>
                </td>

                <td class="d-none">student_{{ $student->gender }}</td>

                <td class="d-none">
                    @if ($student->studentActivation->active_status == 'active')
                        active
                    @else
                        suspended
                    @endif
                </td>
                <td class="d-none">{{ $student->class_id }}_{{ $student->class->class_numeral }}_ucms</td>
                <td>{{ $student->class->name }}</td>
                <td class="d-none">
                    {{ $student->batch_id }}_{{ $student->batch->name }}_{{ $student->branch->branch_name }}
                </td>
                <td>{{ $student->batch->name }}</td>
                <td>{{ $student->institution->name }} (EIIN: {{ $student->institution->eiin_number }})
                </td>
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
                <td class="d-none">{{ $student->branch_id }}_{{ $student->branch->branch_name }}</td>
                @if ($showBranchColumn)
                    <td>
                        @if ($student->branch)
                            @php
                                $branchName = $student->branch->branch_name;
                                $badgeColor = $branchColors[$branchName] ?? 'badge-light-secondary';
                            @endphp
                            <span class="badge {{ $badgeColor }} rounded-pill">{{ $branchName }}</span>
                        @else
                            <span class="badge badge-light-secondary rounded-pill">N/A</span>
                        @endif
                    </td>
                @endif

                <td>
                    <a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-outline ki-down fs-5 m-0"></i></a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4"
                        data-kt-menu="true">

                        @if ($canDownloadForm && optional($student->studentActivation)->active_status == 'active')
                            <div class="menu-item px-3">
                                <a href="{{ route('students.download', $student->id) }}"
                                    class="menu-link text-hover-primary px-3" target="_blank"><i
                                        class="bi bi-download fs-3 me-2"></i> Download Form</a>
                            </div>
                        @endif

                        @if ($canEdit)
                            <div class="menu-item px-3">
                                <a href="{{ route('students.edit', $student->id) }}"
                                    class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i>
                                    Edit Student</a>
                            </div>
                        @endif

                        @if ($canDelete)
                            {{-- <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3 text-hover-danger delete-student"
                                    data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                    Delete Student</a>
                            </div> --}}
                        @endif
                    </div>
                    <!--end::Menu-->
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
