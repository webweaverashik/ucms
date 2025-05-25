<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $transaction->voucher_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 0;
            margin: 0;
            background-color: #fff;
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid #000;
            padding: 4px 8px;
        }

        .signature-line {
            border-top: 1px dotted #000;
            margin-top: 4px;
            width: 120px;
            text-align: center;
        }

        .info p {
            margin-top: 5px;
            margin-bottom: 0;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .logo-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-title img {
            width: 50px;
            vertical-align: middle;
        }

        .logo-title h2 {
            display: inline-block;
            font-size: 20px;
            margin: 0;
            vertical-align: middle;
        }

        .row-table {
            width: 100%;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .row-table td {
            padding: 4px 0;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="logo-title">
        <img src="{{ public_path('img/uc-blue-logo.png') }}" alt="Logo" width="60">
        <h2>Unique Coaching</h2>
        <div>
            <small>{{ $transaction->student->branch->address }}</small><br>
            <small>Phone: {{ $transaction->student->branch->phone_number }}</small>
        </div>
    </div>

    <div class="info">
        <p><strong>Invoice No:</strong> {{ $transaction->paymentInvoice->invoice_number }}</p>
        <p><strong>Receipt No:</strong> {{ $transaction->voucher_no }}</p>
        <p><strong>Name:</strong> {{ $transaction->student->name ?? '' }}
            ({{ $transaction->student->student_unique_id ?? '' }})</p>

        <table class="row-table">
            <tr>
                <td><strong>Class:</strong> {{ $transaction->student->class->class_numeral ?? '' }}</td>
                <td style="text-align: right;"><strong>Shift:</strong> {{ $transaction->student->shift->name ?? '' }}
                </td>
            </tr>
        </table>

        <table class="table bordered-table" width="100%" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Details</th>
                    <th style="text-align: center;">Amount (Tk)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tuition Fee</td>
                    <td style="text-align: center;">{{ $transaction->paymentInvoice->total_amount }}</td>
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
                    <td style="text-align: center;">{{ $due }}</td>
                </tr>
                <tr>
                    <th>Total</th>
                    <th style="text-align: center;">{{ $transaction->paymentInvoice->total_amount }}</th>
                </tr>
            </tbody>
        </table>

        <p>Paid Amount:<strong> {{ $transaction->amount_paid }} Tk</strong></p>
        <p>Due Amount:<strong> {{ $transaction->paymentInvoice->amount_due }} Tk</strong></p>

        <table class="signature-table" style="width: 100%; margin-top: 20px;">
            <tr>
                <td style="text-align: left;">
                    <div style="text-align: left;">
                        <span style="font-style: italic; font-weight: bold;">{{ explode(' ', Auth::user()->name)[0] }}</span><br>
                        <div class="signature-line">Payment Collector</div>
                    </div>
                </td>
                <td style="text-align: right;">
                    <div style="text-align: center;">
                        <span style="font-style: italic; font-weight: bold;">Ashfaq</span><br>
                        <div class="signature-line">Authorized By</div>
                    </div>
                </td>
            </tr>
        </table>


        <div class="footer-note">
            Printed on: {{ now()->format('d-m-Y h:i:s A') }}
        </div>
    </div>
</body>

</html>
