<?php

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
}
