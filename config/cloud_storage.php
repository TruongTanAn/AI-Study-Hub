<?php

class CloudStorage {
    private static $config = [
        'provider' => 'local',
        'local' => [
            'upload_dir' => __DIR__ . '/../uploads/documents/',
            'base_url' => '/AI-Study-Hubb/uploads/documents/'
        ]
    ];

    public static function getConfig() {
        return self::$config;
    }

    public static function upload($fileTmpName, $filename, $folder = 'documents') {
        return self::uploadToLocal($fileTmpName, $filename, $folder);
    }

    public static function uploadToLocal($fileTmpName, $filename, $folder = 'documents') {
        $uploadDir = self::$config['local']['upload_dir'];

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
                'url' => self::$config['local']['base_url'] . $filename,
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
}
