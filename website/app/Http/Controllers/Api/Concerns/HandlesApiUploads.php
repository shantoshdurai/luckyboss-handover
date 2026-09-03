<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/// Mirrors the web portal upload convention: files land in public/uploads/*
/// and the database stores the relative path.
trait HandlesApiUploads
{
    protected function storePublicUpload(UploadedFile $file, string $folder, string $prefix): string
    {
        $directory = public_path('uploads/'.$folder);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = $prefix.'-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();
        $file->move($directory, $name);

        return 'uploads/'.$folder.'/'.$name;
    }

    protected function deletePublicUpload(?string $relativePath): void
    {
        if (blank($relativePath)) {
            return;
        }

        $absolute = public_path($relativePath);

        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    protected function publicUploadUrl(?string $relativePath): ?string
    {
        return blank($relativePath) ? null : asset($relativePath);
    }
}
