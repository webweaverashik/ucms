@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Finance Reports')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Finance Reports
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Reports </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Finance </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title w-100">
                <form id="finance_report_form" class="row g-3 align-items-end w-100">
                    <!-- Date Selection -->
                    <div class="col-md-5">
                        <label for="finance_daterangepicker" class="form-label fw-semibold required">Select Date</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-calendar fs-3"></i>
                            </span>
                            <input type="text" class="form-control form-control-solid rounded-start-0 border-start"
                                placeholder="Pick date range" id="finance_daterangepicker" name="date_range">
                        </div>
                    </div>

                    <!-- Branch Selection -->
                    <div class="col-md-5">
                        <label for="student_paid_sheet_group" class="form-label fw-semibold required">Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_paid_sheet_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="branch_id"
                                data-control="select2" data-placeholder="Select branch" data-hide-search="true">
                                <option></option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @if ($loop->first) selected @endif>
                                        {{ $branch->branch_name }}
                                        ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary" id="generate_finance_report">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Notes Distribution Panel-->
        <div class="card-body py-10">
            <div id="finance_report_result">
            </div>

            <div class="mt-10">
                <canvas id="finance_report_graph" height="80"></canvas>
            </div>

        </div>
        <!--end::Notes Distribution Panel-->
    </div>
    <!--end::Card-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')

<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#finance_report_form').on('submit', function(e) {
        e.preventDefault();
        let formData = $(this).serialize();

        $.ajax({
            url: "{{ route('reports.finance.generate') }}",
            type: "POST",
            data: formData,
            success: function(response) {
                const report = response.report;
                const classes = response.classes;

                if (Object.keys(report).length === 0) {
                    $("#finance_report_result").html(
                        `<div class="alert alert-warning">No data found</div>`
                    );
                    if (window.financeChart) window.financeChart.destroy();
                    return;
                }

                // ----------- TABLE ------------
                let table = `
<h4 class="text-center mb-3">Revenue vs Cost</h4>
<table class="table table-bordered table-striped text-center mb-0" style="border-radius: 0.5rem; overflow: hidden;">
    <thead>
        <tr>
            <th rowspan="2" class="align-middle">Date</th>
            <th colspan="${classes.length}">Revenue (Tk)</th>
            <th rowspan="2" class="align-middle">Total (Tk)</th>
        </tr>
        <tr>`;

                classes.forEach(cls => {
                    table += `<th>${cls}</th>`;
                });
                table += `</tr></thead><tbody>`;

                // Dates descending
                Object.keys(report).sort((a, b) => new Date(b.split('-').reverse().join('-')) - new Date(a.split('-').reverse().join('-')))
                    .forEach(date => {
                        table += `<tr><td>${date}</td>`;
                        let dailyTotal = 0;
                        classes.forEach(cls => {
                            let amount = report[date][cls] ?? 0;
                            dailyTotal += amount;
                            table += `<td>${amount}</td>`;
                        });
                        table += `<td><b>${dailyTotal}</b></td></tr>`;
                    });

                // Grand total row
                table += `<tr><td><b>Total</b></td>`;
                classes.forEach(cls => {
                    let classTotal = Object.values(report).reduce((sum, day) => sum + (day[cls] ?? 0), 0);
                    table += `<td><b>${classTotal}</b></td>`;
                });
                let grandTotal = Object.values(report).reduce((sum, day) => sum + Object.values(day).reduce((a, b) => a + b, 0), 0);
                table += `<td><b>${grandTotal}</b></td></tr>`;

                table += `</tbody></table>`;
                $("#finance_report_result").html(table);

                // ----------- BAR CHART ------------
                let classTotals = {};
                classes.forEach(cls => {
                    classTotals[cls] = Object.values(report).reduce((sum, day) => sum + (day[cls] ?? 0), 0);
                });

                let ctx = document.getElementById('finance_report_graph').getContext('2d');

                if (window.financeChart) {
                    window.financeChart.destroy();
                }

                window.financeChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(classTotals),
                        datasets: [{
                            label: 'Total Revenue',
                            data: Object.values(classTotals),
                            backgroundColor: 'rgba(54, 162, 235, 0.7)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            title: {
                                display: true,
                                text: 'Total Revenue per Class'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: ' + context.raw;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { title: { display: true, text: 'Class Name' } },
                            y: { 
                                beginAtZero: true, 
                                title: { display: true, text: 'Revenue (Tk)' }
                            }
                        }
                    }
                });
            },
            error: function(xhr) {
                $("#finance_report_result").html(
                    `<div class="alert alert-danger">Error loading report.</div>`
                );
                if (window.financeChart) window.financeChart.destroy();
            }
        });
    });
});
</script>


    <script src="{{ asset('js/reports/finance/index.js') }}"></script>

    <script>
        document.getElementById("reports_menu").classList.add("here", "show");
        document.getElementById("finance_report_link").classList.add("active");
    </script>
@endpush
