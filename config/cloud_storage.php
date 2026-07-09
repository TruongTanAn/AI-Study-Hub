<?php

<<<<<<< HEAD
class CloudStorage {
    private static function detectBaseUrl(): string {
        // Auto-detect the base path from the script location
        // For Docker: /var/www/html/ -> /
        // For XAMPP: /AI-Study-Hubb/ -> /AI-Study-Hubb/
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($scriptName);
        // Go up two levels (e.g. /pages/login.php -> /)
        $parts = explode('/', trim($dir, '/'));
        if (count($parts) >= 2) {
            $base = '/' . $parts[0] . '/';
        } else {
            $base = '/';
        }
        return $base . 'uploads/documents/';
    }

    private static $config = [
        'provider' => 'local',
        'local' => [
            'upload_dir' => __DIR__ . '/../uploads/documents/',
            'base_url' => null // computed dynamically
        ]
    ];

    public static function getConfig() {
        $cfg = self::$config;
        if ($cfg['local']['base_url'] === null) {
            $cfg['local']['base_url'] = self::detectBaseUrl();
        }
        return $cfg;
    }

    public static function upload($fileTmpName, $filename, $folder = 'documents') {
        return self::uploadToLocal($fileTmpName, $filename, $folder);
    }

    public static function uploadToLocal($fileTmpName, $filename, $folder = 'documents') {
        $config = self::getConfig();
        $uploadDir = $config['local']['upload_dir'];

        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return [
                    'success' => false,
                    'error' => 'Không thể tạo thư mục upload'
                ];
            }
        }

        if (!is_writable($uploadDir)) {
            return [
                'success' => false,
                'error' => 'Thư mục upload không có quyền ghi'
            ];
        }

        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($fileTmpName, $targetPath)) {
            chmod($targetPath, 0644);
            return [
                'success' => true,
                'url' => $config['local']['base_url'] . $filename,
                'path' => $targetPath
            ];
        }

        return [
            'success' => false,
            'error' => 'Không thể di chuyển file'
        ];
    }

    public static function delete($path) {
        if (empty($path)) {
            return false;
        }

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }
=======
define('CLOUD_STORAGE_ENABLED', false);
define('CLOUD_STORAGE_PROVIDER', 'local');

define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', ['pdf', 'docx', 'pptx']);

function getCloudStorageConfig() {
    return [
        'enabled' => CLOUD_STORAGE_ENABLED,
        'provider' => CLOUD_STORAGE_PROVIDER,
        'upload_max_size' => UPLOAD_MAX_SIZE,
        'allowed_types' => ALLOWED_FILE_TYPES
    ];
>>>>>>> f9777c795a599b3177a3020d309477735eab22fa
}
