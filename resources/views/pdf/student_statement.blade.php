<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ইউনিক কোচিং - টাকা প্রদানের রশিদ (Bootstrap 5)</title>

    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Base font applied to the body */
        body {
            font-family: 'SolaimanLipi', sans-serif;
            padding: 0 6.35mm;
            font-size: 1rem;
            /* Adjusted base size */
            color: #333;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="d-flex flex-column mx-auto">
        <div style="height: 35mm; width: 100%; background: red;">
        </div>

        <div class="row">
            <div class="col-8">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">নাম:</td>
                        <td>{{ $student->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">শ্রেণি:</td>
                        <td>{{ $student->class->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-8 mt-1">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">আইডি:</td>
                        <td>{{ $student->student_unique_id }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4 mt-1">
                <table>
                    <tr>
                        <td style="width: 50px; background: yellow;">ব্যাচ:</td>
                        <td>{{ $student->batch->name }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <h6 class="text-center fw-bold">মাসিক বেতন</h6>


        @php
            $months = [
                'জানুয়ারি',
                'ফেব্রুয়ারি',
                'মার্চ',
                'এপ্রিল',
                'মে',
                'জুন',
                'জুলাই',
                'আগস্ট',
                'সেপ্টেম্বর',
                'অক্টোবর',
                'নভেম্বর',
                'ডিসেম্বর',
            ];
            $chunks = array_chunk($months, 6);
        @endphp

        @foreach ($chunks as $chunkIndex => $chunk)
    <table class="table table-bordered text-center mb-4">
        <thead class="table-secondary">
            <tr>
                <th>মাস</th>
                @foreach ($chunk as $month)
                    <th>{{ $month }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>বেতন</th>
                @foreach ($chunk as $i => $month)
                    @php
                        $monthNumber = ($chunkIndex * 6) + ($i + 1);
                        $transaction = $monthlyPayments->get($monthNumber)?->first();
                    @endphp
                    <td>{{ $transaction->amount_paid ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>রশিদ নং</th>
                @foreach ($chunk as $i => $month)
                    @php
                        $monthNumber = ($chunkIndex * 6) + ($i + 1);
                        $transaction = $monthlyPayments->get($monthNumber)?->first();
                    @endphp
                    <td>{{ $transaction->paymentInvoice->invoice_number ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>গ্রহীতার নাম</th>
                @foreach ($chunk as $i => $month)
                    @php
                        $monthNumber = ($chunkIndex * 6) + ($i + 1);
                        $transaction = $monthlyPayments->get($monthNumber)?->first();
                    @endphp
                    <td>{{ $transaction->createdBy->name ?? '-' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>তারিখ</th>
                @foreach ($chunk as $i => $month)
                    @php
                        $monthNumber = ($chunkIndex * 6) + ($i + 1);
                        $transaction = $monthlyPayments->get($monthNumber)?->first();
                    @endphp
                    <td>{{ optional($transaction?->created_at)->format('d-M-Y') ?? '-' }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
@endforeach



        <p style="display: flex; align-items: center;">
            বকেয়া বেতনের পরিমাণ:
            <span style="flex: 1; border-bottom: 1px dotted #000; margin-left: 5px;">
                4000 taka
            </span>
        </p>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
