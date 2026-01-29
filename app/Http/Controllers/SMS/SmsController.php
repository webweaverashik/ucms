<?php
namespace App\Http\Controllers\SMS;

use App\Http\Controllers\Controller;
use App\Models\SMS\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    // SMS Logs page (no data, AJAX will fetch)
    public function smsLog()
    {
        return view('sms.logs');
    }

    // AJAX: Get SMS Logs for DataTable
    public function getSmsLogsData(Request $request)
    {
        $query = SmsLog::with('createdBy:id,name')
            ->select('id', 'recipient', 'message_body', 'status', 'api_error', 'created_by', 'created_at', 'updated_at');

        // Get total count before filtering
        $totalRecords = SmsLog::count();

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            // Map filter value to actual status
            $statusMap = [
                'S_SUCCESS' => 'SUCCESS',
                'S_FAILED'  => 'FAILED',
                'S_PENDING' => 'PENDING',
            ];
            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        // Search functionality
        if ($request->filled('search') && ! empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('recipient', 'like', "%{$searchValue}%")
                    ->orWhere('message_body', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereHas('createdBy', function ($q2) use ($searchValue) {
                        $q2->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Get filtered count
        $filteredRecords = $query->count();

        // Ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection   = $request->input('order.0.dir', 'desc');

        $columns = ['id', 'recipient', 'message_body', 'status', 'api_error', 'updated_at', 'created_by'];

        if (isset($columns[$orderColumnIndex])) {
            $orderColumn = $columns[$orderColumnIndex];
            if ($orderColumn === 'created_by') {
                // Order by user name requires join
                $query->leftJoin('users', 'sms_logs.created_by', '=', 'users.id')
                    ->orderBy('users.name', $orderDirection)
                    ->select('sms_logs.*');
            } else {
                $query->orderBy($orderColumn, $orderDirection);
            }
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        // Pagination
        $start  = $request->input('start', 0);
        $length = $request->input('length', 10);

        $data = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $formattedData = [];
        foreach ($data as $index => $log) {
            // Format status badge
            $statusBadge = '';
            switch ($log->status) {
                case 'PENDING':
                    $statusBadge = '<span class="badge badge-secondary">Pending</span>';
                    break;
                case 'SUCCESS':
                    $statusBadge = '<span class="badge badge-success">Success</span>';
                    break;
                case 'FAILED':
                    $statusBadge = '<span class="badge badge-danger">Failed</span>';
                    break;
                default:
                    $statusBadge = '<span class="badge badge-light">' . e($log->status) . '</span>';
            }

            // Format API error
            $apiError = '';
            if ($log->api_error) {
                $json              = json_encode($log->api_error, JSON_PRETTY_PRINT);
                $jsonWithoutBraces = trim($json, '{}');
                $apiError          = '<pre class="mb-0" style="font-size: 11px;">' . e($jsonWithoutBraces) . '</pre>';
            }

            // Format action button
            $actionButton = '';
            if ($log->status === 'FAILED') {
                $actionButton = '<a href="#" title="Retry SMS" class="btn btn-icon btn-light-warning btn-sm retry-sms me-2" data-sms-log-id="' . $log->id . '">
                    <i class="ki-outline ki-arrows-circle fs-4"></i>
                </a>';
            }

            $formattedData[] = [
                'DT_RowIndex'  => $start + $index + 1,
                'id'           => $log->id,
                'recipient'    => e($log->recipient),
                'message_body' => e($log->message_body),
                'status'       => $log->status,
                'status_badge' => $statusBadge,
                'api_error'    => $apiError,
                'sent_at'      => $log->updated_at->format('h:i:s A, d-M-Y'),
                'sent_by'      => $log->createdBy->name ?? 'System',
                'action'       => $actionButton,
            ];
        }

        return response()->json([
            'draw'            => intval($request->draw),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $formattedData,
        ]);
    }

    // AJAX: Get all SMS logs for export
    public function exportSmsLogs(Request $request)
    {
        $query = SmsLog::with('createdBy:id,name')
            ->select('id', 'recipient', 'message_body', 'status', 'api_error', 'created_by', 'created_at', 'updated_at');

        // Status filter
        if ($request->filled('status')) {
            $status    = $request->status;
            $statusMap = [
                'S_SUCCESS' => 'SUCCESS',
                'S_FAILED'  => 'FAILED',
                'S_PENDING' => 'PENDING',
            ];
            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        // Search
        if ($request->filled('search')) {
            $searchValue = $request->search;
            $query->where(function ($q) use ($searchValue) {
                $q->where('recipient', 'like', "%{$searchValue}%")
                    ->orWhere('message_body', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhereHas('createdBy', function ($q2) use ($searchValue) {
                        $q2->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        $data = $query->orderBy('updated_at', 'desc')->get();

        $exportData = [];
        foreach ($data as $index => $log) {
            $apiError = '';
            if ($log->api_error) {
                $apiError = json_encode($log->api_error);
            }

            $exportData[] = [
                'sl'           => $index + 1,
                'recipient'    => $log->recipient,
                'message_body' => $log->message_body,
                'status'       => $log->status,
                'api_error'    => $apiError,
                'sent_at'      => $log->updated_at->format('h:i:s A, d-M-Y'),
                'sent_by'      => $log->createdBy->name ?? 'System',
            ];
        }

        return response()->json(['data' => $exportData]);
    }

    // AJAX: Retry failed SMS
    public function retrySms(Request $request, $id)
    {
        $smsLog = SmsLog::findOrFail($id);

        if ($smsLog->status !== 'FAILED') {
            return response()->json([
                'success' => false,
                'message' => 'Only failed SMS can be retried.',
            ], 400);
        }

        $userId = auth()->id();

        try {
            $newLog = $this->smsService->sendSingleSms(
                $smsLog->recipient,
                $smsLog->message_body,
                $smsLog->message_type ?? 'TEXT',
                $userId
            );

            if ($newLog && $newLog->status === 'SUCCESS') {
                // Optionally update the original log or just use the new one
                return response()->json([
                    'success'    => true,
                    'message'    => 'SMS retry successful!',
                    'new_log_id' => $newLog->id,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'SMS retry failed. Please try again.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Single SMS Form page
    public function sendSingleIndex()
    {
        return view('sms.single');
    }

    // Send single SMS
    public function sendSingle(Request $request)
    {
        $data = $request->validate([
            'mobile'       => 'required|string',
            'message_body' => 'required|string',
            'message_type' => 'required|in:TEXT,UNICODE',
        ]);

        $userId = auth()->id();
        $log    = $this->smsService->sendSingleSms($data['mobile'], $data['message_body'], $data['message_type'], $userId);

        $flashKey     = $log->status === 'SUCCESS' ? 'success' : 'error';
        $flashMessage = $log->status === 'SUCCESS' ? 'SMS sent successfully.' : 'SMS sending failed.';

        return redirect()->back()->with($flashKey, $flashMessage);
    }

    // Check SMS balance
    public function checkBalance()
    {
        $balance = $this->smsService->checkBalance();

        if (($balance['api_response_code'] ?? 0) === 200) {
            return response()->json(['balance' => $balance['balance']['sms']]);
        } else {
            return response()->json(['message' => 'Failed to fetch balance.'], 500);
        }
    }

    // Check SMS status by SMS UID
    public function checkSmsStatus(Request $request)
    {
        $request->validate([
            'sms_uid' => 'required|string',
        ]);

        $status = $this->smsService->checkSmsStatus($request->sms_uid);

        if (($status['api_response_code'] ?? 0) === 200) {
            return response()->json(['sms' => $status['sms']]);
        } else {
            return response()->json(['message' => 'Failed to fetch SMS status.'], 500);
        }
    }
}
