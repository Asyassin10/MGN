<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $connection = config('database.default');
        $driver = DB::connection($connection)->getDriverName();

        return match ($driver) {
            'sqlite' => $this->downloadSqlite($connection),
            'pgsql' => $this->downloadPostgres($connection),
            default => abort(Response::HTTP_UNPROCESSABLE_ENTITY, "Sauvegarde non prise en charge pour le pilote {$driver}."),
        };
    }

    private function downloadSqlite(string $connection): BinaryFileResponse
    {
        $sourcePath = config("database.connections.{$connection}.database");

        abort_unless(is_string($sourcePath) && is_file($sourcePath), Response::HTTP_NOT_FOUND, 'Fichier de base SQLite introuvable.');

        $backupPath = storage_path('app/private/database-backup-'.now()->format('Ymd-His').'-'.Str::uuid().'.sqlite');
        abort_unless(copy($sourcePath, $backupPath), Response::HTTP_INTERNAL_SERVER_ERROR, 'Impossible de créer la sauvegarde SQLite.');

        return response()->download($backupPath, 'droguerie-p-backup-'.now()->format('Ymd-His').'.sqlite')->deleteFileAfterSend(true);
    }

    private function downloadPostgres(string $connection): BinaryFileResponse
    {
        $config = config("database.connections.{$connection}");
        $backupPath = storage_path('app/private/database-backup-'.now()->format('Ymd-His').'-'.Str::uuid().'.sql');
        $binary = config('database.backup.pg_dump_binary');

        $result = Process::timeout(120)
            ->env([
                'PGPASSWORD' => (string) ($config['password'] ?? ''),
                'PGSSLMODE' => (string) ($config['sslmode'] ?? 'prefer'),
            ])
            ->run([
                $binary,
                '--format=plain',
                '--no-owner',
                '--no-privileges',
                '--file='.$backupPath,
                '--host='.(string) $config['host'],
                '--port='.(string) $config['port'],
                '--username='.(string) $config['username'],
                (string) $config['database'],
            ]);

        if ($result->failed() || ! is_file($backupPath)) {
            report(new \RuntimeException('La sauvegarde PostgreSQL a échoué : '.$result->errorOutput()));
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Impossible de créer la sauvegarde PostgreSQL.');
        }

        return response()->download($backupPath, 'droguerie-p-backup-'.now()->format('Ymd-His').'.sql')->deleteFileAfterSend(true);
    }
}
