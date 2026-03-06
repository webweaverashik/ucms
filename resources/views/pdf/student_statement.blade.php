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
            
            // Calculate tuition fee due from monthly payments
            $tuitionFeeDue = $monthlyPayments->sum(fn($m) => $m->total_due ?? 0);
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
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                @if (!$monthData)
                                    {{-- No invoice exists - keep empty --}}
                                @elseif ($monthData->total_paid > 0)
                                    {{ $numto->bnCommaLakh($monthData->total_paid) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>বকেয়া</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                @if (!$monthData)
                                    {{-- No invoice exists - keep empty --}}
                                @elseif ($monthData->total_due > 0)
                                    {{ $numto->bnCommaLakh($monthData->total_due) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>রশিদ নং</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                @if (!$monthData)
                                    {{-- No invoice exists - keep empty --}}
                                @elseif ($monthData->invoice_number)
                                    {{ $monthData->invoice_number }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>গ্রহীতার নাম</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                @if (!$monthData)
                                    {{-- No invoice exists - keep empty --}}
                                @elseif ($monthData->receiver_name)
                                    {{ explode(' ', $monthData->receiver_name)[0] }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th>তারিখ</th>
                        @foreach ($chunk as $i => $month)
                            @php
                                $monthNumber = $chunkIndex * 6 + ($i + 1);
                                $monthData = $monthlyPayments->get($monthNumber);
                            @endphp
                            <td>
                                @if (!$monthData)
                                    {{-- No invoice exists - keep empty --}}
                                @elseif ($monthData->last_payment_date)
                                    {{ ashikBnNumericDate($monthData->last_payment_date) }}
                                @else
                                    -
                                @endif
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
            // Group other fee invoices by type
            $groupedByType = $otherFeeInvoices->groupBy(fn($invoice) => $invoice->invoiceType->type_name);

            $typeLabels = [
                'Model Test Fee' => 'মডেল টেস্ট ফি',
                'Exam Fee' => 'পরীক্ষা ফি',
                'Sheet Fee' => 'শীট ফি',
                'Admission Fee' => 'ভর্তি ফি',
                'Diary Fee' => 'ডায়েরি ফি',
                'Book Fee' => 'বই ফি',
                'Others Fee' => 'অন্যান্য',
            ];

            $feeTypes = collect(array_keys($typeLabels));

            // Calculate total other fee due
            $otherFeeDue = $otherFeeInvoices->sum('amount_due');
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
                <tr>
                    <th>পরিমাণ</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $invoicesOfType = $groupedByType[$type] ?? collect();
                            $hasInvoice = $invoicesOfType->isNotEmpty();
                            $totalAmount = $invoicesOfType->sum(function ($invoice) {
                                return $invoice->paymentTransactions->sum('amount_paid');
                            });
                        @endphp
                        <td>
                            @if (!$hasInvoice)
                                {{-- No invoice exists - keep empty --}}
                            @elseif ($totalAmount > 0)
                                {{ $numto->bnCommaLakh($totalAmount) }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <th>বকেয়া</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $invoicesOfType = $groupedByType[$type] ?? collect();
                            $totalDue = $invoicesOfType->sum('amount_due');
                        @endphp
                        <td>
                            @if ($invoicesOfType->isNotEmpty())
                                {{ $totalDue > 0 ? $numto->bnCommaLakh($totalDue) : '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <th>রশিদ নং</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $invoicesOfType = $groupedByType[$type] ?? collect();
                            $firstInvoice = $invoicesOfType->first();
                        @endphp
                        <td>{{ $firstInvoice?->invoice_number ?? '' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <th>গ্রহীতার নাম</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $invoicesOfType = $groupedByType[$type] ?? collect();
                            $hasInvoice = $invoicesOfType->isNotEmpty();
                            $lastTransaction = $invoicesOfType
                                ->flatMap(fn($i) => $i->paymentTransactions)
                                ->sortByDesc('created_at')
                                ->first();
                            $receiverName = $lastTransaction?->createdBy?->name;
                        @endphp
                        <td>
                            @if (!$hasInvoice)
                                {{-- No invoice exists - keep empty --}}
                            @elseif ($receiverName)
                                {{ explode(' ', $receiverName)[0] }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <th>তারিখ</th>
                    @foreach ($feeTypes as $type)
                        @php
                            $invoicesOfType = $groupedByType[$type] ?? collect();
                            $hasInvoice = $invoicesOfType->isNotEmpty();
                            $lastTransaction = $invoicesOfType
                                ->flatMap(fn($i) => $i->paymentTransactions)
                                ->sortByDesc('created_at')
                                ->first();
                            $paymentDate = $lastTransaction?->created_at;
                        @endphp
                        <td>
                            @if (!$hasInvoice)
                                {{-- No invoice exists - keep empty --}}
                            @elseif ($paymentDate)
                                {{ ashikBnNumericDate($paymentDate) }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <div style="width: 60%; font-weight: bold;" class="mb-2">
            <div style="display: flex; align-items: center;">
                <span style="white-space: nowrap;">বকেয়া পরিমাণ:</span>
                <span style="flex: 1; border-bottom: 1px dotted #000; margin-left: 6px; padding-bottom: 2px;">
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
        }, 500);
    };
    window.onafterprint = function() {
        window.close();
    };
</script>
