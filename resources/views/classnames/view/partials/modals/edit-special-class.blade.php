<!--begin::Modal - Edit Special Class-->
<div class="modal fade" id="kt_modal_edit_special_class" tabindex="-1" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header" id="kt_modal_edit_special_class_header">
                <!--begin::Modal title-->
                <h2 class="fw-bold" id="kt_modal_edit_special_class_title">Edit Special Class</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-icon-primary"
                    data-kt-edit-special-class-modal-action="close">
                    <i class="ki-outline ki-cross fs-1">
                    </i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body px-5 my-5">
                <!--begin::Form-->
                <form id="kt_modal_edit_special_class_form" class="form" action="#" novalidate="novalidate">
                    <!--begin::Scroll-->
                    <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_special_class_scroll"
                        data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                        data-kt-scroll-dependencies="#kt_modal_edit_special_class_header"
                        data-kt-scroll-wrappers="#kt_modal_edit_special_class_scroll" data-kt-scroll-offset="300px">
                        {{-- Hidden Input --}}
                        <input type="hidden" name="secondary_class_id" id="edit_secondary_class_id" />
                        <!--begin::Name Input group-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Special Class Name</label>
                            <input type="text" name="name" id="edit_special_class_name"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. ICT Lab"
                                required />
                        </div>
                        <!--end::Name Input group-->
                        <!--begin::Payment Type Input (Read-only)-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Payment Type <span class="text-muted">(Cannot
                                    change)</span></label>
                            <input type="text" id="edit_payment_type_display"
                                class="form-control form-control-solid bg-light-secondary" readonly disabled />
                            <input type="hidden" name="payment_type" id="edit_payment_type" />
                        </div>
                        <!--end::Payment Type Input-->
                        <!--begin::Fee Amount Input group-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                            <input type="number" name="fee_amount" id="edit_fee_amount"
                                class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 500"
                                min="0" required />
                        </div>
                        <!--end::Fee Amount Input group-->
                    </div>
                    <!--end::Scroll-->
                    <!--begin::Actions-->
                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3"
                            data-kt-edit-special-class-modal-action="cancel">Discard</button>
                        <button type="submit" class="btn btn-primary"
                            data-kt-edit-special-class-modal-action="submit">
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
<!--end::Modal - Edit Special Class-->
