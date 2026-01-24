<!--begin::Modal - Add Subject-->
<div class="modal fade" id="kt_modal_add_subject" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_add_subject_header">
                <!--begin::Modal title-->
                <h2 class="fw-bold">Create a new subject</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-subject-modal-action="close">
                    <i class="ki-outline ki-cross fs-1">
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-5">
                <!--begin::Form-->
                <form id="kt_modal_add_subject_form" class="form" action="#" novalidate="novalidate">
                    <!--begin::Scroll-->
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_subject_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_add_subject_header"
                        data-kt-scroll-wrappers="#kt_modal_add_subject_scroll" data-kt-scroll-offset="300px">
                        {{-- Hidden Input --}}
                        <input type="hidden" name="subject_class" value="{{ $classname->id }}" />
                        <!--begin::Subject name Input group-->
                        <div class="fv-row mb-7">
                            <!--begin::Label-->
                            <label class="required fw-semibold fs-6 mb-2">Subject Name</label>
                            <!-- end::Label-->
                            <!--begin::Input-->
                            <input type="text" name="subject_name"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Physics"
                                required />
                            <!--end::Input-->
                        </div>
                        <!--end::Subject name Input group-->
                        <!--begin::Group Input-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Academic Group</label>
                            <select name="subject_group" class="form-select form-select-solid"
                                data-dropdown-parent="#kt_modal_add_subject" data-control="select2"
                                data-hide-search="true" data-placeholder="Select group" required>
                                <option></option>
                                <option value="General" selected>General</option>
                                @if ((int) $classname->class_numeral >= 9)
                                    <option value="Science">Science</option>
                                    <option value="Commerce">Commerce</option>
                                @endif
                            </select>
                        </div>
                        <!--end::Group Input-->
                    </div>
                    <!--end::Scroll-->
                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3"
                            data-kt-add-subject-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-add-subject-modal-action="submit">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Add Subject-->
