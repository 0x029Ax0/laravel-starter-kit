<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;

final class ImageService
{
    /**
     * processUpload
     *
     * This method processes an incoming uploaded file and saves it on disk in the given directory.
     *
     * @param       UploadedFile            The file that was uploaded
     * @param       string                  Directory within /storage/public/images/ to place image in
     * @return string The public path to the uploaded image
     */
    public function processUpload(UploadedFile $file, string $directory): string
    {
        $path = $file->store('images/'.$directory, 'public');

        return 'storage/'.$path;
    }
}
