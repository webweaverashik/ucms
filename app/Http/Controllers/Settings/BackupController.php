<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    protected $disk = 'local';
    protected $path;

    public function __construct()
    {
        $this->path = config('backup.backup.name', 'Laravel') . '/';
    }

    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Activity Not Allowed.');
        }
        
        $backups    = $this->getBackups();
        $totalSize  = $this->getTotalSize($backups);
        $lastBackup = $this->getLastBackup($backups);

        return view('settings.misc.backup', compact('backups', 'totalSize', 'lastBackup'));
    }

    public function create(Request $request)
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);

            // Send email notification if requested
            if ($request->boolean('notify', false)) {
                $this->notifyBackupManagers();
            }

            $backups = $this->getBackups();

            if ($request->ajax()) {
                return response()->json([
                    'success'     => true,
                    'message'     => 'Backup created successfully!',
                    'backups'     => $backups,
                    'total_size'  => $this->getTotalSize($backups),
                    'last_backup' => $this->getLastBackup($backups),
                ]);
            }

            return back()->with('success', 'Backup created successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Backup failed: ' . $e->getMessage(),
                    ],
                    500,
                );
            }

            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = $this->path . $filename;

        if (! $this->isValidFilename($filename) || ! Storage::disk($this->disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($this->disk)->download($path);
    }

    public function destroy(Request $request, $filename)
    {
        $path = $this->path . $filename;

        if (! $this->isValidFilename($filename) || ! Storage::disk($this->disk)->exists($path)) {
            if ($request->ajax()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Backup file not found.',
                    ],
                    404,
                );
            }
            return back()->with('error', 'Backup file not found.');
        }

        Storage::disk($this->disk)->delete($path);
        $backups = $this->getBackups();

        if ($request->ajax()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Backup deleted successfully!',
                'backups'     => $backups,
                'total_size'  => $this->getTotalSize($backups),
                'last_backup' => $this->getLastBackup($backups),
            ]);
        }

        return back()->with('success', 'Backup deleted successfully!');
    }

    protected function getBackups(): array
    {
        $timezone = config('app.timezone', 'Asia/Dhaka');

        // Check if path exists first
        if (! Storage::disk($this->disk)->exists($this->path)) {
            return [];
        }

        $files = Storage::disk($this->disk)->files($this->path);

        if (empty($files)) {
            return [];
        }

        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.zip'))
            ->map(function ($file) use ($timezone) {
                $timestamp = Storage::disk($this->disk)->lastModified($file);
                $filename  = basename($file);
                return [
                    'filename'       => $filename,
                    'size'           => Storage::disk($this->disk)->size($file),
                    'size_formatted' => $this->formatBytes(Storage::disk($this->disk)->size($file)),
                    'date'           => $timestamp,
                    'date_formatted' => Carbon::createFromTimestamp($timestamp, $timezone)->format('M d, Y h:i A'),
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }

    protected function getTotalSize(array $backups): string
    {
        $totalBytes = collect($backups)->sum('size');
        return $this->formatBytes($totalBytes);
    }

    protected function getLastBackup(array $backups): string
    {
        if (empty($backups)) {
            return 'Never';
        }
        return $backups[0]['date_formatted'] ?? 'Never';
    }

    protected function notifyBackupManagers(): void
    {
        $users = User::role('admin')->where('is_active', true)->get();

        $appName   = config('backup.backup.name', 'UCMS');
        $timezone  = config('app.timezone', 'Asia/Dhaka');
        $timestamp = Carbon::now($timezone)->format('M d, Y h:i A');

        foreach ($users as $user) {
            try {
                Mail::raw("Hello {$user->name},\n\nA new database backup was created for {$appName} on {$timestamp}.\n\nRegards,\n{$appName} System", function ($message) use ($user, $appName) {
                    $message->to($user->email)->subject("[{$appName}] Database Backup Created Successfully");
                });
            } catch (\Exception $e) {
                \Log::warning("Failed to send backup notification to {$user->email}: " . $e->getMessage());
            }
        }
    }

    protected function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    protected function isValidFilename($filename): bool
    {
        return preg_match('/^[\w\-\.]+\.zip$/', $filename) && ! str_contains($filename, '..');
    }
}
