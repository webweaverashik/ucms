@extends('layouts.app')

@section('title', 'Promote Students')

@section('header-title')
    <div class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">Promote Students</h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">Student Info</li>
            <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
            <li class="breadcrumb-item text-muted">Promote</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card mb-5 mb-xl-10">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    {{-- Branch Selection --}}
                    @if(!auth()->user()->isAdmin())
                        <div class="w-200px">
                            <label class="form-label fw-bold required">Branch</label>
                            <div class="form-control form-control-solid">{{ auth()->user()->branch->branch_name }}</div>
                            {{-- Hidden select to maintain JS compatibility --}}
                            <select id="source_branch" class="d-none">
                                <option value="{{ auth()->user()->branch_id }}" selected>{{ auth()->user()->branch->branch_name }}</option>
                            </select>
                        </div>
                    @else
                        <div class="w-200px">
                            <label class="form-label fw-bold required">Source Branch</label>
                            <select id="source_branch" class="form-select form-select-solid" data-control="select2">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="w-200px">
                        <label class="form-label fw-bold required">Source Class</label>
                        <select id="source_class" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Class">
                            <option></option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" data-numeral="{{ $class->class_numeral }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-200px d-none" id="academic_group_container">
                        <label class="form-label fw-bold">Academic Group</label>
                        <select id="source_group" class="form-select form-select-solid" data-control="select2" data-placeholder="All Groups" disabled>
                            <option value="">All Groups</option>
                            <option value="Science">Science</option>
                            <option value="Commerce">Commerce</option>
                            <option value="Arts">Arts</option>
                        </select>
                    </div>
                    <div class="w-200px">
                        <label class="form-label fw-bold">Source Batch</label>
                        <select id="source_batch" class="form-select form-select-solid" data-control="select2" data-placeholder="All Batches" @if(auth()->user()->isAdmin()) disabled @endif>
                            <option value="">All Batches</option>
                            @if(!auth()->user()->isAdmin())
                                @foreach($managerBatches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="pt-8">
                        <button type="button" id="btn_filter" class="btn btn-primary">
                            <i class="ki-outline ki-magnifier fs-2"></i> Load Students
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body py-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="students_table">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="check_all" />
                                </div>
                            </th>
                            <th class="w-25px">#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Current Class</th>
                            <th>Batch</th>
                            <th>Group</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold" id="student_list_body">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-10">Select filters and click Load Students</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Target Selection Card -->
    <div class="card d-none" id="target_card">
        <div class="card-header">
            <h3 class="card-title">Target Promotion Details</h3>
        </div>
        <div class="card-body">
            <form id="promotion_form">
                <div class="row g-9 mb-8">
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Target Class</label>
                        <select name="target_class_id" id="target_class" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Class">
                            <option></option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" data-numeral="{{ $class->class_numeral }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 fv-row">
                        <label class="required fs-6 fw-semibold mb-2">Target Batch</label>
                        <select name="target_batch_id" id="target_batch" class="form-select form-select-solid" data-control="select2" data-placeholder="Select Batch">
                            <option></option>
                            @if(!auth()->user()->isAdmin())
                                @foreach($managerBatches as $batch)
                                    <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" id="btn_submit_promote" class="btn btn-success w-250px">
                        <span class="indicator-label">Promote Selected Students</span>
                        <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('page-js')
    <script src="{{ asset('js/students/promote.js') }}"></script>
    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("promote_students_link").classList.add("active");
    </script>
@endpush
