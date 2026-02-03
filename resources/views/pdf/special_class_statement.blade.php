<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->name }} - {{ $secondaryClass->name }} বেতন রশিদ ({{ $year }})</title>
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A5 portrait;
            margin: 0;
        }

        @media print {
            html,
            body {
                width: 148mm;
                height: 210mm;
                margin: 0;
            }

            .d-flex.flex-column.mx-auto {
                width: 100%;
                height: 100%;
            }
        }

        body {
            font-family: 'SolaimanLipi', sans-serif;
            font-size: 10px;
            color: #000;
            padding: 0 3mm 0 5mm;
            width: 148mm;
            height: 210mm;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            transform: scale(1);
            transform-origin: top center;
            background-image: url("{{ asset('pdf/money-receipt.jpg') }}");
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        .table-tight> :not(caption)>*>* {
            padding: 2px !important;
        }
    </style>
</head>

<body>

    @php
        use Rakibhstu\Banglanumber\NumberToBangla;

        $numto = new NumberToBangla();
        $yearBn = ashikBnNum($year);

        // Dynamic label based on secondary class payment type
        $paymentTypeLabel = $secondaryClass->payment_type === 'monthly' ? 'মাসিক' : 'এককালীন';
    @endphp

    <div class="d-flex flex-column mx-auto">
        <div style="height: 39mm; width: 100%; background: transparent;">
        </div>

        <div class="row fw-bold">
            <div class="col-8">
                <table>
                    <tr>
                        <td style="width: 55px; background: transparent;"></td>
                        <td style="font-size: 12px;">{{ $student->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4">
                <table>
                    <tr>
                        <td style="width: 15px; background: transparent;"></td>
                        <td style="font-size: 12px;">{{ $student->student_unique_id }}</td>
                    </tr>
                </table>
            </div>

            <div class="col-8 mt-2">
                <table>
                    <tr>
                        <td style="width: 55px; background: transparent;"></td>
                        <td style="font-size: 12px;">{{ $student->class->name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-4 mt-2">
                <table>
                    <tr>
                        <td style="width: 15px; background: transparent;"></td>
                        <td style="font-size: 12px;">{{ ashikBatchBn($student->batch->name) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Special Class Name Header --}}
        <h6 class="text-center fw-bold mt-3" style="font-size: 12px;">
            {{ $secondaryClass->name }} - {{ $paymentTypeLabel }} বেতন ({{ $yearBn }})
        </h6>

        {{-- 12-month grid - same format for both 'monthly' and 'one_time' payment types --}}
        @php
            $months = [
                'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
                'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর',
            ];
            $chunks = array_chunk($months, 6);
        @endphp

        @foreach ($chunks as $chunkIndex => $chunk)
            <table class="table table-sm table-bordered border-dark text-center w-100 mb-2 table-tight"
                style="table-layout: fixed;">
                <thead class="table-secondary border-dark">
                    <tr>
                        <th>মাস</th>
                        @foreach ($chunk as $month)
                            <th>{{ $month }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    {{-- বেতন (Sum of total payments for the month) --}}
                    <tr>
                        <th>বেতন</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                {{ $monthData && $monthData->total_paid > 0 ? $numto->bnCommaLakh($monthData->total_paid) : '' }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- বকেয়া (Due) --}}
                    <tr>
                        <th>বকেয়া</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                {{ $monthData && $monthData->total_due > 0 ? $numto->bnCommaLakh($monthData->total_due) : '' }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- পরিশোধ সংখ্যা (Payment Count) - Instead of রশিদ নং --}}
                    {{-- <tr>
                        <th>পরিশোধ সংখ্যা</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                {{ $monthData && $monthData->payment_count > 0 ? $numto->bnNum($monthData->payment_count) : '' }}
                            </td>
                        @endforeach
                    </tr> --}}

                    {{-- গ্রহীতার নাম --}}
                    <tr>
                        <th>গ্রহীতার নাম</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                                $receiver = $monthData?->receiver_name;
                            @endphp
                            <td>
                                {{ $receiver ? explode(' ', $receiver)[0] : '' }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- তারিখ --}}
                    <tr>
                        <th>তারিখ</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                                $date = $monthData?->last_payment_date;
                            @endphp
                            <td>
                                {{ $date ? ashikBnNumericDate($date) : '' }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endforeach

        <p style="display: flex; align-items: center; font-weight: bold;" class="mb-2">
            বকেয়া বেতনের পরিমাণ:
            <span style="flex: 1; border-bottom: 1px dotted #000; margin-left: 5px;">
                {{ $numto->bnMoney($totalDue) }}
            </span>
        </p>

        {{-- NO "অন্যান্য ফি সমূহ" section for special class statement --}}

        <table style="width: 60%" class="table table-sm table-bordered border-dark text-center mb-1">
            <tr style="vertical-align: middle;">
                <th style="width: 30%;" class="align-center bg-light">সর্বমোট পরিশোধ</th>
                <td style="width: 70%">
                    {{ $numto->bnCommaLakh($totalPaid) }}/-
                    <p style="display: flex; align-items: center; margin-bottom: 0;">
                        কথায়:
                        <span style="flex: 1; border-bottom: 1px dotted #000; margin-left: 5px;">
                            {{ $numto->bnMoney($totalPaid) }}
                        </span>
                    </p>
                </td>
            </tr>
        </table>

        <div style="height: 25mm; width: 100%; background: transparent;">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
    window.onafterprint = function() {
        window.close();
    };
</script>