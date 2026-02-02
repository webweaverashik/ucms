<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $student->name }} - টাকা প্রদানের রশিদ ({{ $year }})</title>

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
        use Illuminate\Support\Str;

        $numto = new NumberToBangla();

        // Convert year to Bangla
        $yearBn = ashikBnNum($year);
    @endphp

    <div class="d-flex flex-column mx-auto">
        <div style="height: 46mm; width: 100%; background: transparent;">
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


        <h6 class="text-center fw-bold my-1" style="font-size: 10px;">মাসিক বেতন ({{ $yearBn }})</h6>

        @php
            $months = [
                'জানুয়ারি',
                'ফেব্রুয়ারি',
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

            // Calculate total tuition fee dues from filtered monthlyPayments
            $tuitionFeeDue = $monthlyPayments->flatten(1)->sum(fn($t) => optional($t->paymentInvoice)->amount_due ?? 0);
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
                    <tr>
                        <th>বেতন</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $transaction = $monthlyPayments->get($monthNumber)?->first();
                            @endphp
                            <td>
                                {{ $transaction?->amount_paid ? $numto->bnCommaLakh($transaction->amount_paid) : '' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>বকেয়া</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $transaction = $monthlyPayments->get($monthNumber)?->first();
                            @endphp
                            <td>
                                {{ $transaction?->paymentInvoice->amount_due ? $numto->bnCommaLakh($transaction->paymentInvoice->amount_due) : '' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>রশিদ নং</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $transaction = $monthlyPayments->get($monthNumber)?->first();
                            @endphp
                            <td>
                                {{ $transaction ? $transaction->paymentInvoice?->invoice_number : '' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>গ্রহীতার নাম</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $transaction = $monthlyPayments->get($monthNumber)?->first();
                            @endphp
                            <td>
                                {{ $transaction && $transaction->createdBy ? explode(' ', $transaction->createdBy->name)[0] : '' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>তারিখ</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $transaction = $monthlyPayments->get($monthNumber)?->first();
                            @endphp
                            <td>{{ $transaction?->created_at ? ashikBnNumericDate($transaction->created_at) : '' }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        @endforeach

        <p style="display: flex; align-items: center; font-weight: bold;" class="mb-2">
            বকেয়া বেতনের পরিমাণ:
            <span style="flex: 1; border-bottom: 1px dotted #000; margin-left: 5px;">
                {{ $numto->bnMoney($tuitionFeeDue) }}
            </span>
        </p>


        <h6 class="text-center fw-bold mb-1 mt-0" style="font-size: 10px;">অন্যান্য ফি সমূহ ({{ $yearBn }})</h6>

        @php
            // Filter all non-tuition transactions from the year
            $otherPayments = $transactions->filter(
                fn($t) => $t->paymentInvoice->invoiceType->type_name !== 'Tuition Fee',
            );

            // Group by invoice type
            $groupedByType = $otherPayments->groupBy(fn($t) => $t->paymentInvoice->invoiceType->type_name);

            // Define Bangla labels for all known invoice types
            $typeLabels = [
                'Model Test Fee' => 'মডেল টেস্ট ফি',
                'Exam Fee' => 'পরীক্ষা ফি',
                'Sheet Fee' => 'শীট ফি',
                'Admission Fee' => 'ভর্তি ফি',
                'Diary Fee' => 'ডায়েরি ফি',
                'Book Fee' => 'বই ফি',
                'Others Fee' => 'অন্যান্য',
            ];

            // Always display all known types as columns, even if empty
            $feeTypes = collect(array_keys($typeLabels));

            // Calculate total other fees due
            $otherFeeDue = $otherPayments
                ->groupBy('payment_invoice_id')
                ->map(fn($group) => optional($group->first()->paymentInvoice)->amount_due ?? 0)
                ->sum();
        @endphp

        <table class="table table-sm table-bordered border-dark text-center w-100 mb-2 table-tight"
            style="table-layout: fixed;">
            <thead class="table-secondary border-dark">
                <tr>
                    <th style="width: 15%;">ফি ধরণ</th>
                    @foreach ($feeTypes as $type)
                        <th style="width: {{ 85 / count($feeTypes) }}%;">
                            {{ $typeLabels[$type] ?? Str::title(str_replace('_', ' ', $type)) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{-- Amount row --}}
                <tr>
                    <th>পরিমাণ</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $transactionsOfType = $groupedByType[$type] ?? collect();
                            $totalAmount = $transactionsOfType->sum('amount_paid');
                        @endphp
                        <td>{{ $totalAmount > 0 ? $numto->bnCommaLakh($totalAmount) : '' }}</td>
                    @endforeach
                </tr>

                {{-- Due Row --}}
                <tr>
                    <th>বকেয়া</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $transactionsOfType = $groupedByType[$type] ?? collect();
                            // Group by invoice and get sum of dues (to handle multiple transactions per invoice)
                            $totalDue = $transactionsOfType
                                ->groupBy('payment_invoice_id')
                                ->map(fn($group) => optional($group->first()->paymentInvoice)->amount_due ?? 0)
                                ->sum();
                        @endphp
                        <td>{{ $totalDue > 0 ? $numto->bnCommaLakh($totalDue) : '' }}</td>
                    @endforeach
                </tr>

                {{-- Voucher row --}}
                <tr>
                    <th>রশিদ নং</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $transactionsOfType = $groupedByType[$type] ?? collect();
                            $invoice = optional($transactionsOfType->first()?->paymentInvoice);
                        @endphp
                        <td>{{ $invoice?->invoice_number ? $invoice->invoice_number : '' }}</td>
                    @endforeach
                </tr>

                {{-- Receiver row --}}
                <tr>
                    <th>গ্রহীতার নাম</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $transactionsOfType = $groupedByType[$type] ?? collect();
                            $receiver = optional($transactionsOfType->first()?->createdBy)->name;
                        @endphp
                        <td>{{ $receiver ? explode(' ', $receiver)[0] : '' }}</td>
                    @endforeach
                </tr>

                {{-- Date row --}}
                <tr>
                    <th>তারিখ</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $transactionsOfType = $groupedByType[$type] ?? collect();
                            $date = $transactionsOfType->first()?->created_at;
                        @endphp
                        <td>{{ $date ? ashikBnNumericDate($date) : '' }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <div style="width: 60%; font-weight: bold;" class="mb-2">
            <div style="display: flex; align-items: center;">
                <span style="white-space: nowrap;">বকেয়া পরিমাণ:</span>
                <span
                    style="
                flex: 1;
                border-bottom: 1px dotted #000;
                margin-left: 6px;
                padding-bottom: 2px;
            ">
                    {{ $numto->bnMoney($otherFeeDue) }}
                </span>
            </div>
        </div>

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
        }, 500); // Delay to allow layout and images to load
    };

    window.onafterprint = function() {
        window.close();
    };
</script>
