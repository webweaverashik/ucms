<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class PaymentInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unpaid_invoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', function ($query) {
                $query->whereNull('deleted_at')->whereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $paid_invoices = PaymentInvoice::where('status', 'paid')
            ->whereHas('student', function ($query) {
                $query->whereNull('deleted_at')->whereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $dueMonths = PaymentInvoice::where('status', '!=', 'paid')
            ->pluck('month_year')
            ->unique()
            ->sortBy(function ($value) {
                return \Carbon\Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        $paidMonths = PaymentInvoice::where('status', 'paid')
            ->pluck('month_year')
            ->unique()
            ->sortBy(function ($value) {
                return \Carbon\Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        return view('invoices.index', compact('unpaid_invoices', 'paid_invoices', 'dueMonths', 'paidMonths'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
