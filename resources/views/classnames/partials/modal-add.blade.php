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

                        <!--begin::Input Group - Class Numeral-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Class Numeral</label>
                            <select name="class_numeral_add" class="form-select form-select-solid"
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
                        <!--end::Input Group-->

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
