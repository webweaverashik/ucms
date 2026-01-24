<!--begin::Modal - Edit class-->
<div class="modal fade" id="kt_modal_edit_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_edit_class_header">
                <!--begin::Modal title-->
                <h2 class="fw-bold" id="kt_modal_edit_class_title">Edit Class</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-class-modal-action="close">
                    <i class="ki-outline ki-cross fs-1">
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-7">
                <!--begin::Form-->
                <form id="kt_modal_edit_class_form" class="form" action="#" novalidate="novalidate">
                    <!--begin::Scroll-->
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_class_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_edit_class_header"
                        data-kt-scroll-wrappers="#kt_modal_edit_class_scroll" data-kt-scroll-offset="300px">
                        <!--begin::Name Input group-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                            <input type="text" name="class_name_edit"
                                class="form-control form-control-solid mb-3 mb-lg-0"
                                placeholder="Write name of the class" required />
                        </div>
                        <!--end::Name Input group-->
                        <!--begin::Name Input group-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Class Numeral <span class="text-muted">(Cannot
                                    change)</span></label>
                            <select name="class_numeral_edit" class="form-select form-select-solid"
                                data-control="select2" data-hide-search="true"
                                data-dropdown-parent="#kt_modal_edit_class" data-placeholder="Select numeral"
                                disabled>
                                <option></option>
                                @for ($i = 12; $i >= 4; $i--)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <!--end::Name Input group-->
                        <input type="hidden" name="activation_status"
                            value="{{ $classname->isActive() ? 'active' : 'inactive' }}" />
                        <!--begin::Name Input group-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Description <span
                                    class="text-muted">(Optional)</span></label>
                            <input type="text" name="description_edit"
                                class="form-control form-control-solid mb-3 mb-lg-0"
                                placeholder="Write something about the class" />
                        </div>
                        <!--end::Name Input group-->
                    </div>
                    <!--end::Scroll-->
                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3"
                            data-kt-edit-class-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary" data-kt-edit-class-modal-action="submit">
                            <span class="indicator-label">Update</span>
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
<!--end::Modal - Edit class-->
