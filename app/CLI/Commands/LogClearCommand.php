<?php

namespace Metamorphose\CLI\Commands;

use Metamorphose\CLI\CommandInterface;

/**
 * Comando para limpar logs
 * 
 * Remove arquivos de log antigos ou todos os logs:
 * - Remove logs mais antigos que X dias (padrão: 7 dias)
 * - Pode remover todos os logs com --all
 * - Suporta confirmação automática com -y
 */
class LogClearCommand implements CommandInterface
{
    public function name(): string
    {
        return 'log:clear';
    }

    public function description(): string
    {
        return 'Clear log files';
    }

    public function handle(array $args): int
    {
        $config = require __DIR__ . '/../../../config/log.php';
        $logPath = $config['path'];

        if (!is_dir($logPath)) {
            echo "Error: Log directory does not exist: {$logPath}\n";
            return 1;
        }

        $autoConfirm = in_array('-y', $args) || in_array('--yes', $args);
        $clearAll = in_array('--all', $args);
        
        // Parse --days option
        $days = 7; // default
        foreach ($args as $arg) {
            if (strpos($arg, '--days=') === 0) {
                $days = (int) substr($arg, 7);
                if ($days < 0) {
                    echo "Error: Days must be a positive number\n";
                    return 1;
                }
            }
        }

        $files = $this->getLogFiles($logPath);
        
        if (empty($files)) {
            echo "No log files found in {$logPath}\n";
            return 0;
        }

        if ($clearAll) {
            return $this->clearAllLogs($files, $logPath, $autoConfirm);
        } else {
            return $this->clearOldLogs($files, $logPath, $days, $autoConfirm);
        }
    }

    private function getLogFiles(string $logPath): array
    {
        $files = [];
        $items = scandir($logPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $filePath = $logPath . '/' . $item;
            if (is_file($filePath) && pathinfo($item, PATHINFO_EXTENSION) === 'log') {
                $files[] = [
                    'name' => $item,
                    'path' => $filePath,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                ];
            }
        }
        
        return $files;
    }

    private function clearAllLogs(array $files, string $logPath, bool $autoConfirm): int
    {
        $totalSize = array_sum(array_column($files, 'size'));
        $totalFiles = count($files);
        
        echo "Found {$totalFiles} log file(s) (" . $this->formatBytes($totalSize) . ")\n";
        echo "All log files will be deleted.\n\n";
        
        if (!$autoConfirm) {
            echo "Are you sure you want to delete all log files? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($line) !== 'yes') {
                echo "Operation cancelled.\n";
                return 0;
            }
        }

        $deleted = 0;
        $deletedSize = 0;
        
        foreach ($files as $file) {
            if (unlink($file['path'])) {
                $deleted++;
                $deletedSize += $file['size'];
            } else {
                echo "Warning: Could not delete {$file['name']}\n";
            }
        }
        
        echo "\n✅ Successfully deleted {$deleted} file(s) (" . $this->formatBytes($deletedSize) . ")\n";
        return 0;
    }

    private function clearOldLogs(array $files, string $logPath, int $days, bool $autoConfirm): int
    {
        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $oldFiles = [];
        $totalSize = 0;
        $today = date('Y-m-d');
        
        foreach ($files as $file) {
            // Check if file modification time is older than cutoff
            // Also check if filename contains a date older than cutoff
            $fileDate = $this->extractDateFromFilename($file['name']);
            $isOldByDate = $fileDate && strtotime($fileDate) < $cutoffTime;
            $isOldByModified = $file['modified'] < $cutoffTime;
            
            if ($isOldByDate || $isOldByModified) {
                $oldFiles[] = $file;
                $totalSize += $file['size'];
            }
        }
        
        if (empty($oldFiles)) {
            echo "No log files older than {$days} day(s) found.\n";
            echo "Total log files: " . count($files) . "\n";
            if (count($files) > 0) {
                echo "\nTo delete all logs, use: php bin/metamorphose log:clear --all\n";
            }
            return 0;
        }
        
        echo "Found " . count($oldFiles) . " log file(s) older than {$days} day(s) (" . $this->formatBytes($totalSize) . ")\n";
        echo "Total log files: " . count($files) . " (keeping " . (count($files) - count($oldFiles)) . " recent file(s))\n\n";
        
        foreach ($oldFiles as $file) {
            $date = date('Y-m-d', $file['modified']);
            $size = $this->formatBytes($file['size']);
            echo "  - {$file['name']} ({$date}, {$size})\n";
        }
        
        echo "\n";
        
        if (!$autoConfirm) {
            echo "Are you sure you want to delete these files? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($line) !== 'yes') {
                echo "Operation cancelled.\n";
                return 0;
            }
        }

        $deleted = 0;
        $deletedSize = 0;
        
        foreach ($oldFiles as $file) {
            if (unlink($file['path'])) {
                $deleted++;
                $deletedSize += $file['size'];
            } else {
                echo "Warning: Could not delete {$file['name']}\n";
            }
        }
        
        echo "\n✅ Successfully deleted {$deleted} file(s) (" . $this->formatBytes($deletedSize) . ")\n";
        return 0;
    }

    private function extractDateFromFilename(string $filename): ?string
    {
        // Try to extract date from filename like "2025-12-17.log"
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

