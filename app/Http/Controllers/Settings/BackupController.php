<?php
namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupController extends Controller
{
    protected $disk = 'local';
    protected $path;
    protected $filesPath;

    public function __construct()
    {
        $this->path      = config('backup.backup.name', 'Laravel') . '/';
        $this->filesPath = config('backup.backup.name', 'Laravel') . '-files/';
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
        $backupType     = $request->input('backup_type', 'database');
        $createdBackups = [];
        $errors         = [];

        try {
            // Run cleanup first (delete backups older than 7 days)
            $this->cleanupOldBackups();

            // Create database backup
            if (in_array($backupType, ['database', 'both'])) {
                try {
                    Artisan::call('backup:run', ['--only-db' => true]);
                    $latestDbBackup = $this->getLatestBackup('database');
                    if ($latestDbBackup) {
                        $createdBackups['database'] = $latestDbBackup;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Database backup failed: ' . $e->getMessage();
                }
            }

            // Create files backup
            if (in_array($backupType, ['files', 'both'])) {
                try {
                    $filesBackup = $this->createFilesBackup();
                    if ($filesBackup) {
                        $createdBackups['files'] = $filesBackup;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Files backup failed: ' . $e->getMessage();
                }
            }

            // Always send email notification
            $this->notifyBackupManagers($backupType, $createdBackups);

            $backups = $this->getBackups();

            if ($request->ajax()) {
                if (empty($createdBackups)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Backup failed: ' . implode(', ', $errors),
                    ], 500);
                }

                return response()->json([
                    'success'         => true,
                    'message'         => 'Backup created successfully!',
                    'backups'         => $backups,
                    'created_backups' => $createdBackups,
                    'total_size'      => $this->getTotalSize($backups),
                    'last_backup'     => $this->getLastBackup($backups),
                    'errors'          => $errors,
                ]);
            }

            return back()->with('success', 'Backup created successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    protected function createFilesBackup(): ?array
    {
        $timezone  = config('app.timezone', 'Asia/Dhaka');
        $timestamp = Carbon::now($timezone)->format('Y-m-d-H-i-s');
        $filename  = 'ucms-files-backup-' . $timestamp . '.zip';

        // Ensure files backup directory exists on the disk
        if (! Storage::disk($this->disk)->exists($this->filesPath)) {
            Storage::disk($this->disk)->makeDirectory($this->filesPath);
        }

        // Get absolute path from the disk driver
        $zipPath   = Storage::disk($this->disk)->path($this->filesPath . $filename);
        $directory = dirname($zipPath);

        // Ensure directory exists physically (in case of symlinks or strange disk configs)
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $zip = new ZipArchive();
        $res = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($res !== true) {
            // Error codes: https://www.php.net/manual/en/ziparchive.open.php
            throw new \Exception("Cannot create zip file at $zipPath. Error code: $res");
        }

        // Add important directories
        $directories = [
            'app'       => base_path('app'),
            'bootstrap' => base_path('bootstrap'),
            'config'    => base_path('config'),
            'database'  => base_path('database'),
            'public'    => base_path('public'),
            'resources' => base_path('resources'),
            'routes'    => base_path('routes'),
            'storage'   => base_path('storage'),
            'tests'     => base_path('tests'),
        ];

        foreach ($directories as $name => $path) {
            if (File::isDirectory($path)) {
                $this->addDirectoryToZip($zip, $path, $name);
            }
        }

        // Add important root files
        $rootFiles = [
            '.env',
            '.env.example',
            '.gitignore',
            '.gitattributes',
            'artisan',
            'composer.json',
            'composer.lock',
            'package.json',
            'README.md',
        ];

        foreach ($rootFiles as $file) {
            $filePath = base_path($file);
            if (File::exists($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        $zip->close();

        return [
            'filename'       => $filename,
            'type'           => 'files',
            'size_formatted' => $this->formatBytes(filesize($zipPath)),
            'download_url'   => route('backup.download', ['filename' => $filename, 'type' => 'files']),
        ];
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $path, string $relativePath): void
    {
        $files = File::allFiles($path);

        // Dynamic backup names to avoid recursion
        $backupName     = config('backup.backup.name', 'Laravel');
        $dbBackupDir    = $backupName;
        $filesBackupDir = $backupName . '-files';

        foreach ($files as $file) {
            $filePath  = $file->getRealPath();
            $localPath = $relativePath . '/' . $file->getRelativePathname();

            // Normalize path for consistent checking (Windows support)
            $normalizedPath = str_replace('\\', '/', $localPath);

            // Skip vendor and node_modules
            if (str_contains($normalizedPath, 'vendor/') || str_contains($normalizedPath, 'node_modules/')) {
                continue;
            }

            // Smart filtering for storage and bootstrap
            if (
                str_contains($normalizedPath, 'storage/framework/') ||
                str_contains($normalizedPath, 'storage/logs/') ||
                str_contains($normalizedPath, 'bootstrap/cache/')
            ) {
                continue;
            }

            // Skip backup directories to avoid recursion (backing up backups)
            if (
                str_contains($normalizedPath, "storage/app/private/{$dbBackupDir}/") ||
                str_contains($normalizedPath, "storage/app/private/{$filesBackupDir}/") ||
                str_contains($normalizedPath, "storage/app/{$dbBackupDir}/") ||
                str_contains($normalizedPath, "storage/app/{$filesBackupDir}/")
            ) {
                continue;
            }

            $zip->addFile($filePath, $localPath);
        }
    }

    public function download(Request $request, $filename)
    {
        $type = $request->query('type', 'database');

        if ($type === 'files') {
            $path = $this->filesPath . $filename;
        } else {
            $path = $this->path . $filename;
        }

        if (! $this->isValidFilename($filename) || ! Storage::disk($this->disk)->exists($path)) {
            abort(404);
        }

        return Storage::disk($this->disk)->download($path);
    }

    public function destroy(Request $request, $filename)
    {
        $type = $request->query('type', 'database');

        if ($type === 'files') {
            $path = $this->filesPath . $filename;
        } else {
            $path = $this->path . $filename;
        }

        if (! $this->isValidFilename($filename) || ! Storage::disk($this->disk)->exists($path)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found.',
                ], 404);
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

    protected function getLatestBackup(string $type): ?array
    {
        $backups = $this->getBackups();

        foreach ($backups as $backup) {
            if ($backup['type'] === $type) {
                return $backup;
            }
        }

        return null;
    }

    protected function getBackups(): array
    {
        $timezone   = config('app.timezone', 'Asia/Dhaka');
        $allBackups = [];

        // Get database backups
        if (Storage::disk($this->disk)->exists($this->path)) {
            $dbFiles = Storage::disk($this->disk)->files($this->path);

            foreach ($dbFiles as $file) {
                if (str_ends_with($file, '.zip')) {
                    $timestamp = Storage::disk($this->disk)->lastModified($file);
                    $filename  = basename($file);

                    $allBackups[] = [
                        'filename'       => $filename,
                        'type'           => 'database',
                        'type_label'     => 'Database',
                        'type_badge'     => 'badge-light-primary',
                        'size'           => Storage::disk($this->disk)->size($file),
                        'size_formatted' => $this->formatBytes(Storage::disk($this->disk)->size($file)),
                        'date'           => $timestamp,
                        'date_formatted' => Carbon::createFromTimestamp($timestamp, $timezone)->format('M d, Y h:i A'),
                        'download_url'   => route('backup.download', ['filename' => $filename, 'type' => 'database']),
                    ];
                }
            }
        }

        // Get files backups
        if (Storage::disk($this->disk)->exists($this->filesPath)) {
            $filesBackups = Storage::disk($this->disk)->files($this->filesPath);

            foreach ($filesBackups as $file) {
                if (str_ends_with($file, '.zip')) {
                    $timestamp = Storage::disk($this->disk)->lastModified($file);
                    $filename  = basename($file);

                    $allBackups[] = [
                        'filename'       => $filename,
                        'type'           => 'files',
                        'type_label'     => 'Files',
                        'type_badge'     => 'badge-light-success',
                        'size'           => Storage::disk($this->disk)->size($file),
                        'size_formatted' => $this->formatBytes(Storage::disk($this->disk)->size($file)),
                        'date'           => $timestamp,
                        'date_formatted' => Carbon::createFromTimestamp($timestamp, $timezone)->format('M d, Y h:i A'),
                        'download_url'   => route('backup.download', ['filename' => $filename, 'type' => 'files']),
                    ];
                }
            }
        }

        // Sort by date descending
        usort($allBackups, fn($a, $b) => $b['date'] <=> $a['date']);

        return $allBackups;
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

    protected function notifyBackupManagers(string $backupType, array $createdBackups): void
    {
        // Skip if no backups were created
        if (empty($createdBackups)) {
            return;
        }

        try {
            $users = User::role('admin')->where('is_active', true)->get();

            // Skip if no admin users found
            if ($users->isEmpty()) {
                \Log::info('No active admin users found to notify about backup.');
                return;
            }

            $appName   = config('backup.backup.name', 'UCMS');
            $timezone  = config('app.timezone', 'Asia/Dhaka');
            $timestamp = Carbon::now($timezone)->format('M d, Y h:i A');

            $typeLabel = match ($backupType) {
                'database' => 'Database',
                'files'    => 'Application Files',
                'both'     => 'Database & Application Files',
                default    => 'Unknown',
            };

            $backupDetails = '';
            foreach ($createdBackups as $type => $backup) {
                $backupDetails .= "\n- " . ucfirst($type) . ": " . $backup['filename'];
            }

            foreach ($users as $user) {
                // Skip invalid or example emails
                if (! $this->isValidEmail($user->email)) {
                    \Log::info("Skipping backup notification for invalid email: {$user->email}");
                    continue;
                }

                try {
                    Mail::raw(
                        "Hello {$user->name},\n\n" .
                        "A new {$typeLabel} backup was created for {$appName} on {$timestamp}.\n\n" .
                        "Backup Details:{$backupDetails}\n\n" .
                        "Regards,\n{$appName} System",
                        function ($message) use ($user, $appName, $typeLabel) {
                            $message->to($user->email)
                                ->subject("[{$appName}] {$typeLabel} Backup Created Successfully");
                        }
                    );

                    \Log::info("Backup notification sent to {$user->email}");
                } catch (\Exception $e) {
                    \Log::warning("Failed to send backup notification to {$user->email}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the backup process
            \Log::warning("Backup notification process failed: " . $e->getMessage());
        }
    }

    protected function cleanupOldBackups(): void
    {
        // 1. Clean up Spatie managed backups (Database)
        try {
            Artisan::call('backup:clean');
        } catch (\Exception $e) {
            \Log::warning('Spatie backup cleanup failed: ' . $e->getMessage());
        }

        // 2. Clean up custom File backups
        try {
            // Keep files for 7 days
            $days      = 7;
            $threshold = Carbon::now()->subDays($days)->timestamp;

            if (Storage::disk($this->disk)->exists($this->filesPath)) {
                $files = Storage::disk($this->disk)->files($this->filesPath);

                foreach ($files as $file) {
                    if (str_ends_with($file, '.zip')) {
                        $lastModified = Storage::disk($this->disk)->lastModified($file);

                        if ($lastModified < $threshold) {
                            Storage::disk($this->disk)->delete($file);
                            \Log::info("Deleted old backup file: {$file}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Custom files backup cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate email address and check for common invalid/example domains
     */
    protected function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        // Check basic email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // List of invalid/example domains to skip
        $invalidDomains = [
            'example.com',
            'example.org',
            'example.net',
            'test.com',
            'test.org',
            'localhost',
            'localhost.localdomain',
            'invalid.com',
            'fake.com',
            'sample.com',
            'demo.com',
            'mailinator.com',
        ];

        $domain = strtolower(substr(strrchr($email, '@'), 1));

        return ! in_array($domain, $invalidDomains);
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
