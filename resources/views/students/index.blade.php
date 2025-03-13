@extends('layouts.app')

@section('title', 'All Students')

@push('vendor-css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css') }}" />
@endpush

@push('page-css')
@endpush

@section('content')
    <div class="container-fluid flex-grow-1 container-p-y">
        <!-- DataTable with Buttons -->
        {{-- <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="datatables-basic table">
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <th>Name</th>
                            <th>Branch Name</th>
                            <th>DoB</th>
                            <th>Class</th>
                            <th>Guardian</th>
                            <th>Institution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    @if ($student->studentActivation->active_status == 'inactive')
                                        <i class="text-danger ti tabler-alert-circle" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Inactive"></i>
                                    @endif
                                    {{ $student->student_unique_id }}
                                </td>
                                <td>
                                    @if ($student->gender == 'male')
                                        <i class="ti tabler-gender-male" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Male"></i>
                                    @elseif($student->gender == 'female')
                                        <i class="ti tabler-gender-female" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Female"></i>
                                    @endif
                                    {{ $student->full_name }}
                                </td>
                                <td>
                                    @if ($student->branch->branch_name == 'Khilgaon')
                                        <span class="badge bg-label-primary">{{ $student->branch->branch_name }}</span>
                                    @elseif($student->branch->branch_name == 'Goran')
                                        <span class="badge bg-label-info">{{ $student->branch->branch_name }}</span>
                                    @endif
                                </td>
                                <td>{{ $student->date_of_birth->format('d-M-Y') }}</td>
                                <td>{{ $student->class->name }}</td>
                                <td>{{ $student->primaryGuardian->first()->name }},
                                    <i>{{ $student->primaryGuardian->first()->pivot->relationship }}</i>
                                </td>
                                <td>{{ $student->institution->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div> --}}
        <!-- Order List Widget -->

              <div class="card mb-6">
                <div class="card-widget-separator-wrapper">
                  <div class="card-body card-widget-separator">
                    <div class="row gy-4 gy-sm-1">
                      <div class="col-sm-6 col-lg-3">
                        <div
                          class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                          <div>
                            <h4 class="mb-0">{{ count($students) }}</h4>
                            <p class="mb-0">Pending Payment</p>
                          </div>
                          <span class="avatar me-sm-6">
                            <span class="avatar-initial bg-label-secondary rounded text-heading">
                              <i class="icon-base ti tabler-calendar-stats icon-26px text-heading"></i>
                            </span>
                          </span>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none me-6" />
                      </div>
                      <div class="col-sm-6 col-lg-3">
                        <div
                          class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                          <div>
                            <h4 class="mb-0">12,689</h4>
                            <p class="mb-0">Completed</p>
                          </div>
                          <span class="avatar p-2 me-lg-6">
                            <span class="avatar-initial bg-label-secondary rounded"
                              ><i class="icon-base ti tabler-checks icon-26px text-heading"></i
                            ></span>
                          </span>
                        </div>
                        <hr class="d-none d-sm-block d-lg-none" />
                      </div>
                      <div class="col-sm-6 col-lg-3">
                        <div
                          class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                          <div>
                            <h4 class="mb-0">124</h4>
                            <p class="mb-0">Refunded</p>
                          </div>
                          <span class="avatar p-2 me-sm-6">
                            <span class="avatar-initial bg-label-secondary rounded"
                              ><i class="icon-base ti tabler-wallet icon-26px text-heading"></i
                            ></span>
                          </span>
                        </div>
                      </div>
                      <div class="col-sm-6 col-lg-3">
                        <div class="d-flex justify-content-between align-items-start">
                          <div>
                            <h4 class="mb-0">32</h4>
                            <p class="mb-0">Failed</p>
                          </div>
                          <span class="avatar p-2">
                            <span class="avatar-initial bg-label-secondary rounded"
                              ><i class="icon-base ti tabler-alert-octagon icon-26px text-heading"></i
                            ></span>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Order List Table -->
              <div class="card">
                <div class="card-datatable table-responsive">
                  <table class="datatables-order table border-top">
                    <thead>
                      <tr>
                        <th></th>
                        <th></th>
                        <th>order</th>
                        <th>date</th>
                        <th>customers</th>
                        <th>payment</th>
                        <th>status</th>
                        <th>method</th>
                        <th>actions</th>
                      </tr>
                    </thead>
                  </table>
                </div>
              </div>
        <!--/ DataTable with Buttons -->
    </div>
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('assets/js/app-ecommerce-order-list.js') }}"></script>
@endpush
