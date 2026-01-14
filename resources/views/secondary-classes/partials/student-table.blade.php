@props([
    'students' => collect(),
    'secondaryClass',
    'classname',
    'branches' => collect(),
    'branchColors' => [],
    'isAdmin' => false,
    'isManager' => false,
    'tableId' => 'kt_students_table',
    'statusType' => 'active' // 'active' or 'inactive'
])

@php
    $studentsByBranch = $isAdmin ? $students->groupBy(fn($enrollment) => $enrollment->student->branch_id ?? 0) : collect();
@endphp

<!--begin::Card-->
<div class="card mb-6 mb-xl-9">
    <!--begin::Header-->
    <div class="card-header">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" data-table-filter="search" data-table-id="{{ $tableId }}"
                    class="form-control form-control-solid w-350px ps-12"
                    placeholder="Search students...">
            </div>
        </div>
        <div class="card-toolbar">
            <div class="d-flex justify-content-end gap-3">
                <!--begin::Group Filter-->
                <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter
                </button>
                <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="separator border-gray-200"></div>
                    <div class="px-7 py-5">
                        <div class="mb-10">
                            <label class="form-label fs-6 fw-semibold">Academic Group:</label>
                            <select class="form-select form-select-solid fw-bold filter-group-select" 
                                data-kt-select2="true"
                                data-table-id="{{ $tableId }}"
                                data-placeholder="Select group" 
                                data-allow-clear="true"
                                data-hide-search="true">
                                <option></option>
                                <option value="Science">Science</option>
                                <option value="Commerce">Commerce</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="reset"
                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true"
                                data-table-filter="reset"
                                data-table-id="{{ $tableId }}">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6"
                                data-kt-menu-dismiss="true"
                                data-table-filter="apply"
                                data-table-id="{{ $tableId }}">Apply</button>
                        </div>
                    </div>
                </div>
                <!--end::Group Filter-->
            </div>
        </div>
    </div>
    <!--end::Header-->

    <!--begin::Card body-->
    <div class="card-body pb-5">
        @if ($isAdmin && $branches->count() > 0)
            <!--begin::Branch Tabs for Admin-->
            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabs_{{ $tableId }}" role="tablist">
                @foreach ($branches as $branch)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="branch-{{ $branch->id }}-{{ $tableId }}-tab"
                            data-bs-toggle="tab" data-bs-target="#branch_{{ $branch->id }}_{{ $tableId }}_content"
                            type="button" role="tab" data-branch-filter="{{ $branch->id }}"
                            data-table-id="{{ $tableId }}">
                            <i class="ki-outline ki-home fs-4 me-2"></i>{{ $branch->branch_name }}
                            <span class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2">
                                {{ $studentsByBranch->get($branch->id, collect())->count() }}
                            </span>
                        </button>
                    </li>
                @endforeach
            </ul>
            <!--end::Branch Tabs-->
        @endif

        <!--begin::Table-->
        <table class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table student-table"
            id="{{ $tableId }}">
            <thead>
                <tr class="fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-30px">#</th>
                    <th>Student</th>
                    <th>Group</th>
                    <th>Batch</th>
                    {{-- <th class="w-150px">Branch</th> --}}
                    <th class="w-120px">Fee</th>
                    @if ($secondaryClass->payment_type === 'monthly')
                        <th class="w-120px">Total Paid</th>
                    @endif
                    <th class="w-120px">Enrolled At</th>
                    <th class="w-120px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @forelse ($students as $enrollment)
                    @php
                        $student = $enrollment->student;
                    @endphp
                    @if ($student)
                        <tr data-branch-id="{{ $student->branch_id }}" data-student-id="{{ $student->id }}"
                            data-enrollment-id="{{ $enrollment->id }}"
                            data-enrollment-status="{{ $enrollment->is_active ? 'active' : 'inactive' }}"
                            data-academic-group="{{ $student->academic_group ?? '' }}">
                            <td class="pe-2">{{ $loop->index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="d-flex flex-column text-start">
                                        <a href="{{ route('students.show', $student->id) }}"
                                            class="@if (!$enrollment->is_active) text-danger @else text-gray-800 text-hover-primary @endif mb-1"
                                            @if (!$enrollment->is_active) title="Inactive Enrollment" data-bs-toggle="tooltip" @endif>
                                            {{ $student->name }}
                                        </a>
                                        <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($student->academic_group == 'Science')
                                    <span class="badge badge-pill badge-info">{{ $student->academic_group }}</span>
                                @elseif ($student->academic_group == 'Commerce')
                                    <span class="badge badge-pill badge-success">{{ $student->academic_group }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $student->batch->name ?? '-' }}</td>
                            {{-- <td>{{ $student->branch->branch_name ?? '-' }}</td> --}}
                            <td>
                                <span
                                    class="amount-display fw-bold text-primary">৳{{ number_format($enrollment->amount, 0) }}</span>
                            </td>
                            @if ($secondaryClass->payment_type === 'monthly')
                                <td>
                                    <span
                                        class="amount-display fw-bold text-success">৳{{ number_format($enrollment->total_paid ?? 0, 0) }}</span>
                                </td>
                            @endif
                            <td>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('d-M-Y') : '-' }}</td>
                            <td>
                                @if (($isAdmin || $isManager) && $secondaryClass->is_active === true)
                                    <div class="d-flex justify-content-center gap-2">
                                        @if ($secondaryClass->payment_type === 'monthly')
                                            <!--begin::Edit Amount-->
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light-primary edit-enrollment"
                                                data-student-id="{{ $student->id }}"
                                                data-student-name="{{ $student->name }}"
                                                data-amount="{{ $enrollment->amount }}" data-bs-toggle="tooltip"
                                                title="Edit Fee Amount">
                                                <i class="ki-outline ki-pencil fs-5"></i>
                                            </button>
                                            <!--end::Edit Amount-->
                                        @endif

                                        <!--begin::Toggle Activation-->
                                        @if ($enrollment->is_active)
                                            <button type="button"
                                                class="btn btn-sm btn-light-danger toggle-enrollment-activation deactivate-btn"
                                                data-student-id="{{ $student->id }}"
                                                data-student-name="{{ $student->name }}"
                                                data-is-active="1" data-bs-toggle="tooltip"
                                                title="Deactivate Enrollment">
                                                <i class="ki-outline ki-cross-circle fs-5 me-1"></i>
                                                <span class="d-none d-md-inline">Deactivate</span>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="btn btn-sm btn-light-success toggle-enrollment-activation activate-btn"
                                                data-student-id="{{ $student->id }}"
                                                data-student-name="{{ $student->name }}"
                                                data-is-active="0" data-bs-toggle="tooltip"
                                                title="Activate Enrollment">
                                                <i class="ki-outline ki-check-circle fs-5 me-1"></i>
                                                <span class="d-none d-md-inline">Activate</span>
                                            </button>
                                        @endif
                                        <!--end::Toggle Activation-->
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endif
                @empty
                    {{-- Empty state handled by DataTable --}}
                @endforelse
            </tbody>
        </table>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->