<?php

class CloudStorage {
    private static $config = [
        'provider' => 'local',
        'local' => [
            'upload_dir' => __DIR__ . '/../uploads/documents/',
            'base_url' => '/AI-Study-Hubb/uploads/documents/'
        ],
        'gcs' => [
            'bucket' => '',
            'key_file' => '',
            'project_id' => '',
            'storage_api' => ''
        ],
        's3' => [
            'region' => '',
            'bucket' => '',
            'key' => '',
            'secret' => ''
        ],
        'azure' => [
            'connection_string' => '',
            'container' => ''
        ]
    ];

    public static function getConfig() {
        return self::$config;
    }

    public static function setProvider($provider) {
        if (in_array($provider, ['local', 'gcs', 's3', 'azure'])) {
            self::$config['provider'] = $provider;
        }
    }

    public static function upload($file, $filename, $folder = 'documents') {
        $provider = self::$config['provider'];

        switch ($provider) {
            case 'gcs':
                return self::uploadToGCS($file, $filename, $folder);
            case 's3':
                return self::uploadToS3($file, $filename, $folder);
            case 'azure':
                return self::uploadToAzure($file, $filename, $folder);
            default:
                return self::uploadToLocal($file, $filename, $folder);
        }
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

        if (file_exists($targetPath)) {
            return [
                'success' => false,
                'error' => 'File đã tồn tại'
            ];
        }

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

    public static function uploadToGCS($file, $filename, $folder) {
        $config = self::$config['gcs'];

        if (empty($config['bucket']) || empty($config['key_file'])) {
            return self::uploadToLocal($file, $filename, $folder);
        }

        try {
            putenv("GOOGLE_APPLICATION_CREDENTIALS=" . $config['key_file']);
            require_once __DIR__ . '/../vendor/autoload.php';

            $storage = new Google\Cloud\Storage\StorageClient([
                'projectId' => $config['project_id']
            ]);

            $bucket = $storage->bucket($config['bucket']);
            $object = $bucket->upload(fopen($file, 'r'), [
                'name' => $folder . '/' . $filename
            ]);

            return [
                'success' => true,
                'url' => 'https://storage.googleapis.com/' . $config['bucket'] . '/' . $folder . '/' . $filename,
                'path' => $folder . '/' . $filename,
                'bucket' => $config['bucket']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Lỗi GCS: ' . $e->getMessage()
            ];
        }
    }

    public static function uploadToS3($file, $filename, $folder) {
        $config = self::$config['s3'];

        if (empty($config['bucket']) || empty($config['key'])) {
            return self::uploadToLocal($file, $filename, $folder);
        }

        try {
            require_once __DIR__ . '/../vendor/autoload.php';

            $s3 = new Aws\S3\S3Client([
                'region' => $config['region'],
                'version' => 'latest',
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret']
                ]
            ]);

            $result = $s3->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $folder . '/' . $filename,
                'Body' => fopen($file, 'r'),
                'ACL' => 'public-read'
            ]);

            return [
                'success' => true,
                'url' => $result['ObjectURL'],
                'path' => $folder . '/' . $filename,
                'bucket' => $config['bucket']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Lỗi S3: ' . $e->getMessage()
            ];
        }
    }

    public static function uploadToAzure($file, $filename, $folder) {
        $config = self::$config['azure'];

        if (empty($config['connection_string']) || empty($config['container'])) {
            return self::uploadToLocal($file, $filename, $folder);
        }

        try {
            require_once __DIR__ . '/../vendor/autoload.php';

            $blobClient = MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($config['connection_string']);

            $content = fopen($file, 'r');
            $blobClient->createBlockBlob($config['container'], $folder . '/' . $filename, $content);

            return [
                'success' => true,
                'url' => 'https://' . $config['container'] . '.blob.core.windows.net/' . $folder . '/' . $filename,
                'path' => $folder . '/' . $filename,
                'container' => $config['container']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Lỗi Azure: ' . $e->getMessage()
            ];
        }
    }

    public static function delete($path, $provider = null) {
        if ($provider === null) {
            $provider = self::$config['provider'];
        }

        switch ($provider) {
            case 'gcs':
                return self::deleteFromGCS($path);
            case 's3':
                return self::deleteFromS3($path);
            case 'azure':
                return self::deleteFromAzure($path);
            default:
                return self::deleteFromLocal($path);
        }
    }

    public static function deleteFromLocal($path) {
        $fullPath = self::$config['local']['upload_dir'] . basename($path);

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    public static function deleteFromGCS($path) {
        return true;
    }

    public static function deleteFromS3($path) {
        return true;
    }

    public static function deleteFromAzure($path) {
        return true;
    }
}
