<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $transaction->voucher_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>

        @page {
            size: 80mm auto;
            margin: 0;
        }

        
        body {
            width: 80mm;
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            padding: 5px 0;
            background-color: #fff;
        }
        
        .bordered-table th,
        .bordered-table td {
            border: 1px solid #000 !important;
            padding: 4px 8px !important;
        }
        
        .signature-line {
            border-top: 1px dotted #000;
            margin-top: 20px;
            width: 120px;
            text-align: center;
            font-size: 13px;
        }
        
        .info p {
            margin-bottom: 4px;
        }
        
        .table th,
        .table td {
            vertical-align: middle;
        }
        
        .logo-title h2 {
            display: inline-block;
            vertical-align: middle;
            margin: 0;
        }
        
        .logo-title img {
            width: 50px;
            vertical-align: middle;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">

        <!-- Header -->
        <div class="text-center mb-3">
            <div class="logo-title">
                <img src="{{ asset('img/uc-blue-logo.png') }}" alt="Logo">
                <h2 class="d-inline-block ms-2 fw-bold">Unique Coaching</h2>
            </div>
            <small class="d-block">{{ $transaction->student->branch->address }}</small>
            <small>Phone: {{ $transaction->student->branch->phone_number }}</small>
        </div>

        <!-- Student Info -->
        <div class="info mb-3">
            <p><strong>Invoice No:</strong> {{ $transaction->paymentInvoice->invoice_number }}</p>
            <p><strong>Receipt No:</strong> {{ $transaction->voucher_no }}</p>
            <p><strong>Name:</strong> {{ $transaction->student->name ?? '' }}
                ({{ $transaction->student->student_unique_id ?? '' }})</p>
            <div class="d-flex justify-content-between mb-1">
                <div><strong>Class:</strong> {{ $transaction->student->class->class_numeral ?? '' }}</div>
                <div><strong>Shift:</strong> {{ $transaction->student->shift->name ?? '' }}</div>
            </div>

            <!-- Payment Table -->
            <table class="table table-bordered bordered-table w-100">
                <thead class="table-light">
                    <tr>
                        <th>Details</th>
                        <th class="text-center">Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tuition Fee</td>
                        <td class="text-center">{{ intval($transaction->paymentInvoice->total_amount) }}</td>
                    </tr>
                    <tr>
                        <td>Model Test Fee</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Admission Fee / Others</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Exam Fee</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>Previous Due</td>


                        @php
                            $previousPaid = $transaction->paymentInvoice->paymentTransactions
                                ->where('id', '<', $transaction->id)
                                ->sum('amount_paid');

                            $due = $transaction->paymentInvoice->total_amount - $previousPaid;
                        @endphp


                        <td class="text-center">{{ intval($due) }}</td>
                    </tr>
                    <tr>
                        <th>Total</th>
                        <th class="text-center">{{ intval($transaction->paymentInvoice->total_amount) }}</th>
                    </tr>
                </tbody>
            </table>

            <!-- Footer Info -->
            <div class="mb-3">
                <p><strong>Paid Amount:</strong> {{ intval($transaction->amount_paid) }} ৳</p>
                <p><strong>Due Amount:</strong> {{ intval($transaction->paymentInvoice->amount_due) }} ৳</p>
            </div>

            <!-- Signatures -->
            <div class="d-flex justify-content-between">
                <div class="signature-line">Payment Collector:</div>
                <div class="signature-line">Authorized By:</div>
            </div>

            {{-- <small class="position-absolute bottom-0">{{ $transaction->created_at->format('d-M-Y h:i:s A') }}</small> --}}
            <small>{{ $transaction->created_at->format('d-M-Y h:i:s A') }}</small>

        </div>
</body>

</html>
<script>
    window.onload = () => window.print();
</script>
