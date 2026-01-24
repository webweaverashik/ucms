<!--begin::Modal - Toggle Activation Student-->
<div class="modal fade" id="kt_toggle_activation_student_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header">
                <!--begin::Modal title-->
                <h2 id="toggle-activation-modal-title">Activation/Deactivation Student</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body py-lg-5">
                <!--begin::Content-->
                <div class="flex-row-fluid p-lg-5">
                    <form action="{{ route('students.toggleActive') }}" class="form d-flex flex-column" method="POST"
                        id="kt_toggle_activation_form">
                        @csrf
                        <!--begin::Left column-->
                        <div class="d-flex flex-column">
                            <input type="hidden" name="student_id" id="student_id" />
                            <input type="hidden" name="active_status" id="activation_status" />
                            <input type="hidden" name="class_id" id="toggle_class_id" />
                            <div class="row">
                                <div class="col-lg-12">
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-column mb-5 fv-row">
                                        <!--begin::Label-->
                                        <label class="fs-5 fw-semibold mb-2 required"
                                            id="reason_label">Activation/Deactivation Reason</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <textarea class="form-control" rows="3" name="reason" id="activation_reason"
                                            placeholder="Write the reason for this update" required minlength="3"></textarea>
                                        <!--end::Input-->
                                        <div class="fv-plugins-message-container invalid-feedback" id="reason_error">
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <!--begin::Button-->
                                <button type="button" class="btn btn-secondary me-5"
                                    data-bs-dismiss="modal">Cancel</button>
                                <!--end::Button-->
                                <!--begin::Button-->
                                <button type="submit" class="btn btn-primary" id="kt_toggle_activation_submit">
                                    <span class="indicator-label">Submit</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                                <!--end::Button-->
                            </div>
                        </div>
                        <!--end::Left column-->
                    </form>
                </div>
                <!--end::Content-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Toggle Activation Student-->