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

        table {
            border-collapse: collapse;
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
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 60px; text-align: left; vertical-align: top;">
                <img src="{{ public_path('img/uc-blue-logo.png') }}" alt="Logo" width="60">
            </td>
            <td style="text-align: left;">
                <h2 style="margin: 0;">Unique Coaching</h2>
                <div>
                    <small>{{ $transaction->student->branch->address }}</small><br>
                    <small>Phone: {{ $transaction->student->branch->phone_number }}</small>
                </div>
            </td>
        </tr>
    </table>

    <div class="info">
        <p>Invoice: <strong>{{ $transaction->paymentInvoice->invoice_number }}</strong></p>
        <p>Voucher: <strong>{{ $transaction->voucher_no }}</strong></p>
        <p>Name: {{ $transaction->student->name ?? '' }}
            ({{ $transaction->student->student_unique_id ?? '' }})</p>

        <table class="row-table">
            <tr>
                <td>Class: {{ $transaction->student->class->class_numeral ?? '' }}</td>
                <td>Shift: {{ $transaction->student->shift->name ?? '' }}
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
                    <td>Tuition Fee
                        ({{ \Carbon\Carbon::createFromFormat('m_Y', $transaction->paymentInvoice->month_year)->format('F Y') }})
                    </td>
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
                    <th>Total Payable</th>
                    <th style="text-align: center;">{{ $transaction->paymentInvoice->total_amount }}</th>
                </tr>
                <tr>
                    <td>(-) Paid Amount</td>
                    <td style="text-align: center;">{{ $transaction->amount_paid }}</td>
                </tr>
                {{-- <tr>
                    <td>Previous Due</td>
                    @php
                        $previousPaid = $transaction->paymentInvoice->paymentTransactions
                            ->where('id', '<', $transaction->id)
                            ->sum('amount_paid');

                        $due = $transaction->paymentInvoice->total_amount - $previousPaid;
                    @endphp
                    <td style="text-align: center;">{{ $due }}</td>
                </tr> --}}
                <tr>
                    <th>Remaining</th>
                    <th style="text-align: center;">{{ $transaction->paymentInvoice->amount_due }}</th>
                </tr>
            </tbody>
        </table>


        <table class="signature-table" style="width: 100%; margin-top: 20px;">
            <tr>
                <td style="text-align: left;">
                    <div style="text-align: left;">
                        <span
                            style="font-style: italic; font-weight: bold;">{{ explode(' ', Auth::user()->name)[0] }}</span><br>
                        <div class="signature-line">Payment Collector</div>
                    </div>
                </td>
            </tr>
        </table>


        <div class="footer-note">
            Paid on: {{ $transaction->created_at->format('d-m-Y h:i:s A') }}
        </div>
    </div>
</body>

</html>
