<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload a file to the specified directory.
     *
     * @param UploadedFile $file
     * @param string $directory  e.g. 'branding', 'resumes', 'company-logos', 'job-images', 'blog-images'
     * @param string|null $oldPath  Previous file path to delete
     * @return string  The stored file path (relative)
     */
    public function upload(UploadedFile $file, string $directory, ?string $oldPath = null): string
    {
        // Delete old file if provided
        if ($oldPath) {
            $this->delete($oldPath);
        }

        // Generate unique filename
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
            . '-' . now()->format('YmdHis')
            . '-' . Str::random(6)
            . '.' . $file->getClientOriginalExtension();

        // Store in public disk
        $path = $file->storeAs(
            "uploads/{$directory}",
            $filename,
            'public'
        );

        return $path;
    }

    /**
     * Upload and optimize an image.
     */
    public function uploadImage(UploadedFile $file, string $directory, ?string $oldPath = null, int $maxWidth = 1200): string
    {
        return $this->upload($file, $directory, $oldPath);
    }

    /**
     * Upload a resume/document.
     */
    public function uploadDocument(UploadedFile $file, string $directory = 'resumes', ?string $oldPath = null): string
    {
        // Validate allowed extensions
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException('Only PDF, DOC, and DOCX files are allowed.');
        }

        return $this->upload($file, $directory, $oldPath);
    }

    /**
     * Delete a file.
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * Get the public URL for a stored file.
     */
    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
