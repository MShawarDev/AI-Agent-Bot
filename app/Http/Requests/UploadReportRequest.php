<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = config('uploads.max_file_kb', 10240);
        $mimes = implode(',', config('uploads.allowed_mimes', ['pdf', 'doc', 'docx', 'xls', 'xlsx']));

        return [
            'file' => [
                'required',
                'file',
                "mimes:{$mimes}",
                "max:{$maxKb}",
            ],
        ];
    }

    public function messages(): array
    {
        $maxMb = round(config('uploads.max_file_kb', 10240) / 1024, 1);
        $types = implode(', ', config('uploads.allowed_mimes', []));

        return [
            'file.mimes' => "Only {$types} files are accepted.",
            'file.max' => "File must be smaller than {$maxMb} MB.",
        ];
    }
}
