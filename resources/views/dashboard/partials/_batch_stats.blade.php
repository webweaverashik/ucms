{{-- Batch-wise Student Statistics --}}
<div class="card mb-5">
    <div class="card-header border-0 pt-5">
        <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold text-gray-900">Batch-wise Student Count</span>
            <span class="text-muted mt-1 fw-semibold fs-7">Distribution across batches</span>
        </h3>
    </div>
    <div class="card-body py-3">
        <div class="row g-4" id="batchStatsGrid">
            {{-- Loading skeleton --}}
            <div class="col-12 text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
