@push('page-css')
    <!-- Dropzone css -->
    <link href="{{ asset('assets/libs/dropzone/dropzone.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'New Admission')


@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            New Admission Form
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="?page=index" class="text-muted text-hover-primary">
                    Admission </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Admission </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body checkout-tab">
                    <form action="#" method="post" id="new-admission-form" class="needs-validation" novalidate>
                        <div class="step-arrow-nav mt-n3 mx-n3 mb-3">

                            <ul class="nav nav-pills nav-justified custom-nav" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3 active" id="pills-bill-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-bill-info" type="button" role="tab"
                                        aria-controls="pills-bill-info" aria-selected="true">
                                        <i
                                            class="ri-user-2-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Student Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-bill-address-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-bill-address" type="button" role="tab"
                                        aria-controls="pills-bill-address" aria-selected="false">
                                        <i
                                            class="ri-truck-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Guardian Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-payment-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-payment" type="button" role="tab"
                                        aria-controls="pills-payment" aria-selected="false">
                                        <i
                                            class="ri-bank-card-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Academic Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link fs-15 p-3" id="pills-finish-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-finish" type="button" role="tab"
                                        aria-controls="pills-finish" aria-selected="false">
                                        <i
                                            class="ri-checkbox-circle-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                        Admission
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pills-bill-info" role="tabpanel"
                                aria-labelledby="pills-bill-info-tab">
                                <div>
                                    <h5 class="mb-1">Student Basic Information</h5>
                                    <p class="text-muted mb-4">Please fill all information below</p>
                                </div>

                                <div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-5">
                                                {{-- <h5 class="fs-14 mb-1">Upload a 300x300 size photo and photo size should not exceed 50kB.</h5> --}}
                                                <h5 class="card-title mb-0">Student Photo</h5>

                                                <p class="text-muted">Upload a photo. File size should not
                                                    exceed 80kB.
                                                </p>
                                                <div class="">
                                                    <div class="position-relative d-inline-block">
                                                        <div class="position-absolute top-100 start-100 translate-middle">
                                                            <label for="profile-photo-input" class="mb-0"
                                                                data-bs-toggle="tooltip" data-bs-placement="right"
                                                                title="Select Image">
                                                                <div class="avatar-xs">
                                                                    <div
                                                                        class="avatar-title bg-light border rounded-circle text-muted cursor-pointer">
                                                                        <i class="ri-image-fill"></i>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                            <input class="form-control d-none" value=""
                                                                id="profile-photo-input" type="file"
                                                                accept="image/png, image/gif, image/jpeg"
                                                                name="student_photo" required>
                                                        </div>
                                                        <div class="avatar-lg">
                                                            <div class="avatar-title bg-light rounded-circle">
                                                                {{-- <img src="" id="student-img" class="avatar-md h-auto" /> --}}
                                                                <img src="{{ asset('assets/images/users/user-dummy-img.jpg') }}"
                                                                    id="student-img"
                                                                    class="avatar-md rounded-circle object-fit-cover" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Name row --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_name_en_input">Full Name <span
                                                        class="text-muted">(In English)</span> <span
                                                        class="text-danger">*</span></label>
                                                <input name="student_name_en" type="text" class="form-control"
                                                    id="student_name_en_input" value=""
                                                    placeholder="Enter student name" required>
                                                <div class="invalid-feedback">Please enter full name in English.</div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_name_bn_input">পূর্ণ নাম <span
                                                        class="text-muted">(বাংলায়)</span>
                                                    <span class="text-danger">*</span></label>
                                                <input name="student_name_bn" type="text" class="form-control"
                                                    id="student_name_bn_input" value=""
                                                    placeholder="শিক্ষার্থীর নাম লিখুন" required>
                                                <div class="invalid-feedback">Please enter full name in Bangla.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_address_input">Home Address <span
                                                        class="text-danger">*</span></label>
                                                <input name="student_address" type="text" class="form-control"
                                                    id="student_address_input" value=""
                                                    placeholder="Enter student's home address" required>
                                                <div class="invalid-feedback">Please provide valid address.</div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_email_input">Email Address
                                                    <span class="text-muted">(Optional)</span></label>
                                                <input name="student_email" type="email" class="form-control"
                                                    placeholder="Enter student email if any" id="student_email_input">
                                                <div class="invalid-feedback">Please, insert a valid email address.</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Phone number row --}}
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_number_home_input">Phone <span
                                                        class="text-muted">(Home)</span>
                                                    <span class="text-danger">*</span></label>
                                                <input name="student_number_home" type="text" class="form-control"
                                                    placeholder="Enter home phone number" id="student_number_home_input"
                                                    required>
                                                <div class="invalid-feedback">Phone number is required for communication.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="meta-title-input">Phone
                                                    <span class="text-muted">(WhatsApp)</span></label>
                                                <input type="text" class="form-control"
                                                    placeholder="Enter WhatsApp number" id="meta-title-input"
                                                    name="student_number_wa">
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="meta-title-input">Phone<span
                                                        class="text-muted">(SMS)</span> <span class="text-danger">*
                                                    </span><span class="badge bg-info-subtle text-info">For
                                                        result/notice</span></label>
                                                <input type="text" class="form-control" placeholder="Enter SMS number"
                                                    id="meta-title-input" name="student_number_sms" required>
                                                <div class="invalid-feedback">SMS number is required for result and notice.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end col -->

                                    {{-- Gender, BirthDate and Religion row --}}
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="meta-title-input">Date of Birth</label>
                                                <input type="date" id="datepicker-publish-input" class="form-control"
                                                    placeholder="Select student date of birth" data-provider="flatpickr"
                                                    data-date-format="d-M-Y">
                                            </div>
                                        </div>

                                        <div class="col-lg-2">
                                            <div class="mb-3">
                                                <label class="form-label" for="meta-title-input">Gender</label>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="stduent_gender"
                                                        id="genderMale" checked>
                                                    <label class="form-check-label" for="genderMale">
                                                        Male
                                                    </label>
                                                </div>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="stduent_gender"
                                                        id="genderFemale">
                                                    <label class="form-check-label" for="genderFemale">
                                                        Female
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-2">
                                            <div class="mb-3">
                                                <label for="choices-publish-visibility-input"
                                                    class="form-label">Religion</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="stduent_religion" id="religionIslam" checked>
                                                    <label class="form-check-label" for="religionIslam">
                                                        Islam
                                                    </label>
                                                </div>

                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="stduent_religion" id="religionHinduism">
                                                    <label class="form-check-label" for="religionHinduism">
                                                        Hinduism
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- end col -->

                                        <div class="col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="student_blood_input">Blood Group</label>
                                                <select class="form-select" id="student_blood_input" data-choices
                                                    data-choices-search-true data-choices-sorting-false>
                                                    <option value="" selected disabled>Select Group</option>
                                                    <option value="A+">A+</option>
                                                    <option value="B+">B+</option>
                                                    <option value="AB+">AB+</option>
                                                    <option value="O+">O+</option>
                                                    <option value="A-">A-</option>
                                                    <option value="B-">B-</option>
                                                    <option value="AB-">AB-</option>
                                                    <option value="O-">O-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- end col -->
                                    </div>

                                    {{-- Next tab button --}}
                                    <div class="d-flex align-items-start gap-3 mt-3">
                                        <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                            data-nexttab="pills-bill-address-tab">
                                            <i class="ri-truck-line label-icon align-middle fs-16 ms-2"></i>Proceed to
                                            Guardian Info
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- end tab pane -->

                            <div class="tab-pane fade" id="pills-bill-address" role="tabpanel"
                                aria-labelledby="pills-bill-address-tab">
                                <div>
                                    <h5 class="mb-1">Guardian Information</h5>
                                    <p class="text-muted mb-4">Please fill all information below</p>
                                </div>

                                <div class="mt-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="flex-grow-1">
                                            <h5 class="fs-14 mb-0">Saved Address</h5>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <!-- Button trigger modal -->
                                            <button type="button" class="btn btn-sm btn-success mb-3"
                                                data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                                Add Address
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row gy-3">
                                        <div class="col-lg-4 col-sm-6">
                                            <div class="form-check card-radio">
                                                <input id="shippingAddress01" name="shippingAddress" type="radio"
                                                    class="form-check-input" checked>
                                                <label class="form-check-label" for="shippingAddress01">
                                                    <span class="mb-4 fw-semibold d-block text-muted text-uppercase">Home
                                                        Address</span>

                                                    <span class="fs-14 mb-2 d-block">Marcus Alfaro</span>
                                                    <span class="text-muted fw-normal text-wrap mb-1 d-block">4739 Bubby
                                                        Drive Austin, TX 78729</span>
                                                    <span class="text-muted fw-normal d-block">Mo. 012-345-6789</span>
                                                </label>
                                            </div>
                                            <div class="d-flex flex-wrap p-2 py-1 bg-light rounded-bottom border mt-n1">
                                                <div>
                                                    <a href="#" class="d-block text-body p-1 px-2"
                                                        data-bs-toggle="modal" data-bs-target="#addAddressModal"><i
                                                            class="ri-pencil-fill text-muted align-bottom me-1"></i>
                                                        Edit</a>
                                                </div>
                                                <div>
                                                    <a href="#" class="d-block text-body p-1 px-2"
                                                        data-bs-toggle="modal" data-bs-target="#removeItemModal"><i
                                                            class="ri-delete-bin-fill text-muted align-bottom me-1"></i>
                                                        Remove</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <div class="form-check card-radio">
                                                <input id="shippingAddress02" name="shippingAddress" type="radio"
                                                    class="form-check-input">
                                                <label class="form-check-label" for="shippingAddress02">
                                                    <span class="mb-4 fw-semibold d-block text-muted text-uppercase">Office
                                                        Address</span>

                                                    <span class="fs-14 mb-2 d-block">James Honda</span>
                                                    <span class="text-muted fw-normal text-wrap mb-1 d-block">1246 Virgil
                                                        Street Pensacola, FL 32501</span>
                                                    <span class="text-muted fw-normal d-block">Mo. 012-345-6789</span>
                                                </label>
                                            </div>
                                            <div class="d-flex flex-wrap p-2 py-1 bg-light rounded-bottom border mt-n1">
                                                <div>
                                                    <a href="#" class="d-block text-body p-1 px-2"
                                                        data-bs-toggle="modal" data-bs-target="#addAddressModal"><i
                                                            class="ri-pencil-fill text-muted align-bottom me-1"></i>
                                                        Edit</a>
                                                </div>
                                                <div>
                                                    <a href="#" class="d-block text-body p-1 px-2"
                                                        data-bs-toggle="modal" data-bs-target="#removeItemModal"><i
                                                            class="ri-delete-bin-fill text-muted align-bottom me-1"></i>
                                                        Remove</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <h5 class="fs-14 mb-3">Shipping Method</h5>

                                        <div class="row g-4">
                                            <div class="col-lg-6">
                                                <div class="form-check card-radio">
                                                    <input id="shippingMethod01" name="shippingMethod" type="radio"
                                                        class="form-check-input" checked>
                                                    <label class="form-check-label" for="shippingMethod01">
                                                        <span
                                                            class="fs-20 float-end mt-2 text-wrap d-block fw-semibold">Free</span>
                                                        <span class="fs-14 mb-1 text-wrap d-block">Free Delivery</span>
                                                        <span class="text-muted fw-normal text-wrap d-block">Expected
                                                            Delivery 3 to 5 Days</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-check card-radio">
                                                    <input id="shippingMethod02" name="shippingMethod" type="radio"
                                                        class="form-check-input" checked>
                                                    <label class="form-check-label" for="shippingMethod02">
                                                        <span
                                                            class="fs-20 float-end mt-2 text-wrap d-block fw-semibold">$24.99</span>
                                                        <span class="fs-14 mb-1 text-wrap d-block">Express Delivery</span>
                                                        <span class="text-muted fw-normal text-wrap d-block">Delivery
                                                            within 24hrs.</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="button" class="btn btn-light btn-label previestab"
                                        data-previous="pills-bill-info-tab"><i
                                            class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Back to
                                        Student Info</button>
                                    <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                        data-nexttab="pills-payment-tab"><i
                                            class="ri-bank-card-line label-icon align-middle fs-16 ms-2"></i>Continue to
                                        Academic Info</button>
                                </div>
                            </div>
                            <!-- end tab pane -->

                            <div class="tab-pane fade" id="pills-payment" role="tabpanel"
                                aria-labelledby="pills-payment-tab">
                                <div>
                                    <h5 class="mb-1">Academic Information</h5>
                                    <p class="text-muted mb-4">Please complete academic information and enrolled subjects
                                    </p>
                                </div>

                                <div class="d-flex align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        {{-- <h5 class="fs-14 mb-0">Saved Address</h5> --}}
                                    </div>
                                    <div class="flex-shrink-0">
                                        <!-- Button trigger modal -->
                                        <button type="button" class="btn btn-sm btn-success mb-3" data-bs-toggle="modal"
                                            data-bs-target="#addAddressModal">
                                            Add School
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="student_institution_input" class="form-label">School/College <span
                                                    class="text-danger">*</span></label>

                                            <select class="form-select" id="student_institution_input" data-choices
                                                data-choices-search-true data-choices-sorting-false required>
                                                <option value="" selected disabled>Search or select institution
                                                </option>
                                                @foreach ($institutions as $institution)
                                                    <option value="{{ $institution->id }}">{{ $institution->name }}
                                                        ({{ $institution->eiin_number }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Institution info is required.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="student_class_input" class="form-label">Class <span
                                                    class="text-danger">*</span></label>
                                            <!-- Class Selection -->
                                            <select class="form-select" id="student_class_input" data-choices
                                                data-choices-search-true data-choices-sorting-false required>
                                                <option value="" selected disabled>Select class</option>
                                                @foreach ($classnames as $classname)
                                                    <option value="{{ $classname->id }}"
                                                        data-class-numeral="{{ $classname->class_numeral }}">
                                                        {{ $classname->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Which class the student is admitted.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6" id="group_section">
                                        <div class="mb-3">
                                            <label for="student_group_input" class="form-label">Group</label>
                                            <select class="form-select" id="student_group_input" data-choices
                                                data-choices-sorting-false>
                                                <option value="" selected disabled>Select group</option>
                                                <option value="Science">Science</option>
                                                <option value="Commerce">Commerce</option>
                                                <option value="Arts">Arts</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="student_shift_input" class="form-label">Shift <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="student_shift_input" data-choices
                                                data-choices-search-false required>
                                                <option value="" selected disabled>Select shift</option>

                                                @foreach ($shifts as $shift)
                                                    <option value="{{ $shift->id }}">{{ $shift->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">Shift info required</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Enrolled Subjects (Dynamically Loaded) -->
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label">Enrolled Subjects</label>
                                            <p class="text-muted">Select all the subjects taken by the student.</p>

                                            <!-- Select All Toggle -->
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select_all_subjects">
                                                <label class="form-check-label fw-bold" for="select_all_subjects">
                                                    Select All
                                                </label>
                                            </div>

                                            <!-- Subject Checkboxes -->
                                            <div id="subject_list">
                                                <!-- Subjects will be loaded here dynamically -->
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-start gap-3 mt-4">
                                        <button type="button" class="btn btn-light btn-label previestab"
                                            data-previous="pills-bill-address-tab"><i
                                                class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>Back to
                                            Guardian Info</button>
                                        <button type="button" class="btn btn-primary btn-label right ms-auto nexttab"
                                            data-nexttab="pills-finish-tab"><i
                                                class="ri-shopping-basket-line label-icon align-middle fs-16 ms-2"></i>Continue
                                            to
                                            Admission Process</button>
                                    </div>
                                </div>
                                <!-- end tab pane -->

                                <div class="tab-pane fade" id="pills-finish" role="tabpanel"
                                    aria-labelledby="pills-finish-tab">
                                    <div class="text-center py-5">

                                        <div class="mb-4">
                                            <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                                colors="primary:#0ab39c,secondary:#405189"
                                                style="width:120px;height:120px"></lord-icon>
                                        </div>
                                        <h5>Thank you ! Your Order is Completed !</h5>
                                        <p class="text-muted">You will receive an order confirmation email with details of
                                            your
                                            order.</p>

                                        <h3 class="fw-semibold">Order ID: <a href="apps-ecommerce-order-details.html"
                                                class="text-decoration-underline">VZ2451</a></h3>
                                    </div>
                                </div>
                                <!-- end tab pane -->
                            </div>
                            <!-- end tab content -->
                    </form>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
@endsection

@section('modals')
    <div id="addAddressModal" class="modal fade zoomIn" tabindex="-1" aria-labelledby="addAddressModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="mb-3">
                            <label for="addaddress-Name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addaddress-Name" placeholder="Enter name">
                        </div>

                        <div class="mb-3">
                            <label for="addaddress-textarea" class="form-label">Address</label>
                            <textarea class="form-control" id="addaddress-textarea" placeholder="Enter address" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="addaddress-Name" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="addaddress-Name"
                                placeholder="Enter phone no.">
                        </div>

                        <div class="mb-3">
                            <label for="state" class="form-label">Address Type</label>
                            <select class="form-select" id="state" data-choices data-choices-search-false>
                                <option value="homeAddress">Home (7am to 10pm)</option>
                                <option value="officeAddress">Office (11am to 7pm)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success">Save</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection


@push('page-js')
    <!-- dropzone min -->
    <script src="{{ asset('assets/libs/dropzone/dropzone-min.js') }}"></script>

    <!-- Dropzone initialization -->
    <script>
        document.querySelector("#profile-photo-input").addEventListener("change", function() {
            var e = document.querySelector("#student-img"),
                t = document.querySelector("#profile-photo-input").files[0];

            // File size restriction (80 KB)
            if (t.size > 80 * 1024) {
                alert("File size must not exceed 80KB.");
                return;
            }

            // Validate image dimensions (must be exactly 300x300px)
            var img = new Image();
            img.onload = function() {
                /*if (img.width !== 300 || img.height !== 300) {
                    alert("Image must be exactly 300x300 pixels.");
                } else { */
                // Load and display image if it meets all conditions
                var o = new FileReader();
                o.addEventListener("load", function() {
                    e.src = o.result;
                }, false);
                o.readAsDataURL(t);
                //    }
            };

            img.src = URL.createObjectURL(t);
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Form validation initialisation
            const form = document.getElementById("new-admission-form");

            form.addEventListener("submit", function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add("was-validated");
            });
        });
    </script>

    {{-- Dynamically show subject list and group --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let classSelect = document.getElementById("student_class_input");
            let groupSelect = document.getElementById("student_group_input");
            let groupSection = document.getElementById("group_section");
            let subjectList = document.getElementById("subject_list");
            let selectAllCheckbox = document.getElementById("select_all_subjects");

            // Hide Group initially
            groupSection.style.display = "none";

            classSelect.addEventListener("change", function() {
                let selectedClass = classSelect.options[classSelect.selectedIndex];
                let classNumeral = selectedClass.getAttribute("data-class-numeral"); // Get class numeral
                let selectedGroup = groupSelect.value; // Get selected group

                // ✅ Hide Group if Class I-VIII is selected and set "General"
                if (parseInt(classNumeral) >= 1 && parseInt(classNumeral) <= 8) {
                    groupSection.style.display = "none"; // Hide the group selection
                    groupSelect.value = "General"; // Auto-set value to General
                } else {
                    groupSection.style.display = "block"; // Show for Class IX+
                    groupSelect.value = ""; // Reset value for IX+
                }

                // ✅ Reload subjects based on new selection
                loadSubjects(selectedClass.value, groupSelect.value);
            });

            groupSelect.addEventListener("change", function() {
                let classId = classSelect.value;
                let selectedGroup = groupSelect.value;
                if (classId) {
                    loadSubjects(classId, selectedGroup);
                }
            });

            function loadSubjects(classId, selectedGroup = null) {
                fetch(`/get-subjects/${classId}`)
                    .then(response => response.json())
                    .then(data => {
                        subjectList.innerHTML = ""; // Clear existing subjects

                        // ✅ Filter subjects based on selected academic_group
                        let filteredSubjects = data.filter(subject => !selectedGroup || subject
                            .academic_group === selectedGroup);

                        // ✅ Sort subjects so mandatory ones come first
                        filteredSubjects.sort((a, b) => b.is_mandatory - a.is_mandatory);

                        if (filteredSubjects.length > 0) {
                            filteredSubjects.forEach(subject => {
                                let checked = subject.is_mandatory ? "checked" :
                                    ""; // ✅ Pre-check but allow unchecking

                                subjectList.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input subject-checkbox" type="checkbox" name="subjects[]" value="${subject.id}" id="subject_${subject.id}" ${checked}>
                                <label class="form-check-label" for="subject_${subject.id}">
                                    ${subject.subject_name} ${subject.is_mandatory ? '<span class="text-danger">*</span>' : ''}
                                </label>
                            </div>`;
                            });

                            // ✅ Enable "Select All" Only If There Are Optional Subjects
                            let optionalSubjects = document.querySelectorAll('.subject-checkbox:not(:checked)');
                            selectAllCheckbox.style.display = optionalSubjects.length > 0 ? "block" : "none";

                            // ✅ Handle "Select All" Click Event
                            selectAllCheckbox.addEventListener("change", function() {
                                optionalSubjects.forEach(checkbox => {
                                    checkbox.checked = selectAllCheckbox.checked;
                                });
                            });
                        } else {
                            subjectList.innerHTML =
                                `<p class="text-muted">No subjects available for this class and group.</p>`;
                            selectAllCheckbox.style.display = "none"; // Hide "Select All" if no subjects
                        }
                    })
                    .catch(error => console.error("Error loading subjects:", error));
            }
        });
    </script>

    <script>
        document.getElementById("admission_menu").classList.add("collapsed", "active");
        document.getElementById("admission_menu").setAttribute("aria-expanded", "true");
        document.getElementById("sidebarAdmission").classList.add("show");
        document.querySelector('[data-key="New Admission"]').classList.add("active");
    </script>
@endpush
