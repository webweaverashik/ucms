<!--begin::Modal - Bulk Toggle Activation Student-->
<div class="modal fade" id="kt_bulk_toggle_activation_modal" tabindex="-1" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header">
                <!--begin::Modal title-->
                <h2 id="bulk-toggle-activation-modal-title">Bulk Activation/Deactivation</h2>
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
                    <form action="{{ route('students.bulkToggleActive') }}" class="form d-flex flex-column"
                        method="POST" id="kt_bulk_toggle_activation_form">
                        @csrf
                        <!--begin::Left column-->
                        <div class="d-flex flex-column">
                            <input type="hidden" name="active_status" id="bulk_activation_status" />
                            
                            <!--begin::Selected students summary-->
                            <div class="mb-5">
                                <div class="alert alert-info d-flex align-items-center p-5">
                                    <i class="ki-outline ki-information-5 fs-2hx text-info me-4"></i>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold">You are about to <span id="bulk_action_type">activate/deactivate</span> <span id="bulk_student_count">0</span> student(s).</span>
                                        <span class="text-muted fs-7">This action will update the status of all selected students.</span>
                                    </div>
                                </div>
                            </div>
                            <!--end::Selected students summary-->
                            
                            <div class="row">
                                <div class="col-lg-12">
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-column mb-5 fv-row">
                                        <!--begin::Label-->
                                        <label class="fs-5 fw-semibold mb-2 required"
                                            id="bulk_reason_label">Reason for this action</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <textarea class="form-control" rows="3" name="reason" id="bulk_activation_reason"
                                            placeholder="Write a common reason for all selected students" required minlength="3"></textarea>
                                        <!--end::Input-->
                                        <div class="fv-plugins-message-container invalid-feedback" id="bulk_reason_error">
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
                                <button type="submit" class="btn btn-primary" id="kt_bulk_toggle_activation_submit">
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
<!--end::Modal - Bulk Toggle Activation Student-->
