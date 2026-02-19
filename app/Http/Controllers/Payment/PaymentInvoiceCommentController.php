<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentInvoiceCommentController extends Controller
{
    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_invoice_id' => 'required|exists:payment_invoices,id',
            'comment'            => 'required|string|min:3|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $validator->errors(),
                ],
                422,
            );
        }

        // // Check if user has permission
        // if (! auth()->user()->can('invoices.edit')) {
        //     return response()->json(
        //         [
        //             'success' => false,
        //             'message' => 'No permission to add comments.',
        //         ],
        //         403,
        //     );
        // }

        $invoice = PaymentInvoice::with('student')->findOrFail($request->payment_invoice_id);

        // Check branch access
        if (auth()->user()->branch_id != 0 && $invoice->student && $invoice->student->branch_id != auth()->user()->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Access denied.',
                ],
                403,
            );
        }

        $comment = PaymentInvoiceComment::create([
            'payment_invoice_id' => $request->payment_invoice_id,
            'comment'            => $request->comment,
            'commented_by'       => auth()->id(),
        ]);

        $comment->load('commentedBy:id,name');

        // Clear cache
        if (function_exists('clearUCMSCaches')) {
            clearUCMSCaches();
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully.',
            'comment' => [
                'id'           => $comment->id,
                'comment'      => $comment->comment,
                'commented_by' => $comment->commentedBy?->name ?? 'Unknown',
                'created_at'   => $comment->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    /**
     * Get comments for an invoice.
     */
    public function getComments(PaymentInvoice $invoice)
    {
        if (! auth()->user()->can('invoices.view')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No permission to view comments.',
                ],
                403,
            );
        }

        // Load student relationship
        $invoice->load('student');

        // Check branch access
        if (auth()->user()->branch_id != 0 && $invoice->student && $invoice->student->branch_id != auth()->user()->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Access denied.',
                ],
                403,
            );
        }

        $comments = $invoice
            ->comments()
            ->with('commentedBy:id,name')
            ->latest()
            ->get()
            ->map(function ($comment) {
                return [
                    'id'           => $comment->id,
                    'comment'      => $comment->comment,
                    'commented_by' => $comment->commentedBy?->name ?? 'Unknown',
                    'created_at'   => $comment->created_at->format('d M Y, h:i A'),
                ];
            });

        return response()->json([
            'success'        => true,
            'comments'       => $comments,
            'invoice_number' => $invoice->invoice_number,
        ]);
    }
}
