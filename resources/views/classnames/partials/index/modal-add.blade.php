@php
    $currentYearPrefix = (int) date('y'); // Get current year's last 2 digits (e.g., 25 for 2025)
    $startYearPrefix = 25;
@endphp

<div class="modal fade" id="kt_modal_add_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <!--begin::Modal Header-->
            <div class="modal-header" id="kt_modal_add_class_header">
                <h2 class="fw-bold">Add New Class</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-class-modal-action="close">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <!--end::Modal Header-->

            <!--begin::Modal Body-->
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_class_form" class="form" action="#" novalidate="novalidate">
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_class_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_add_class_header"
                        data-kt-scroll-wrappers="#kt_modal_add_class_scroll" data-kt-scroll-offset="300px">

                        <!--begin::Input Group - Class Name-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                            <input type="text" name="class_name_add" class="form-control form-control-solid"
                                placeholder="e.g., Class Ten Science" required />
                        </div>
                        <!--end::Input Group-->

                        <!--begin::Row - Class Numeral & Year Prefix-->
                        <div class="row mb-7">
                            <!--begin::Col - Class Numeral-->
                            <div class="col-12" id="class_numeral_add_col">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Class Numeral</label>
                                    <select name="class_numeral_add" id="class_numeral_add_select" 
                                        class="form-select form-select-solid"
                                        data-control="select2" data-hide-search="true"
                                        data-dropdown-parent="#kt_modal_add_class" data-placeholder="Select numeral" required>
                                        <option></option>
                                        @for ($i = 12; $i >= 4; $i--)
                                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <!--end::Col-->

                            <!--begin::Col - Year Prefix (Conditional)-->
                            <div class="col-4 d-none" id="year_prefix_add_col">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Year Prefix</label>
                                    <select name="year_prefix_add" id="year_prefix_add_select"
                                        class="form-select form-select-solid"
                                        data-control="select2" data-hide-search="true"
                                        data-dropdown-parent="#kt_modal_add_class" data-placeholder="Select">
                                        <option></option>
                                        @for ($i = $currentYearPrefix + 1; $i >= $startYearPrefix; $i--)
                                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->

                        <!--begin::Year Prefix Help Text (Conditional)-->
                        <div class="fv-row mb-7 d-none" id="year_prefix_add_help">
                            <div class="form-text text-muted fs-8 mt-n5">
                                <i class="ki-outline ki-information-2 fs-7 me-1"></i>
                                Student ID format: <code>&lt;year_prefix&gt;&lt;class_numeral&gt;&lt;sequence&gt;</code> e.g., <code>261001</code>
                            </div>
                        </div>
                        <!--end::Year Prefix Help Text-->

                        <!--begin::Input Group - Description-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Description
                                <span class="text-muted fs-7">(Optional)</span>
                            </label>
                            <textarea name="description_add" class="form-control form-control-solid"
                                placeholder="Write something about this class..." rows="3"></textarea>
                        </div>
                        <!--end::Input Group-->
                    </div>

                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" data-kt-add-class-modal-action="cancel">
                            Discard
                        </button>
                        <button type="submit" class="btn btn-primary" data-kt-add-class-modal-action="submit">
                            <span class="indicator-label">Create Class</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    <!--end::Actions-->
                </form>
            </div>
            <!--end::Modal Body-->
        </div>
    </div>
</div>