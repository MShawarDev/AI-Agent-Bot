<?php

return [
    'max_file_kb'          => (int) env('UPLOAD_MAX_FILE_KB', 10240),   // 10 MB
    'max_files_per_client' => (int) env('UPLOAD_MAX_FILES_PER_CLIENT', 50),
    'allowed_mimes'        => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
];
