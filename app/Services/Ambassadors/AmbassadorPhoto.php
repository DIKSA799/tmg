<?php

namespace App\Services\Ambassadors;

use App\Models\Ambassador;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class AmbassadorPhoto
{
    /**
     * Square-crop, resize and re-encode the uploaded photo as WebP.
     * One file per ambassador, replaced on every upload.
     */
    public function store(Ambassador $ambassador, UploadedFile $file): string
    {
        $size = (int) config('ambassadors.photo.size', 600);
        $quality = (int) config('ambassadors.photo.quality', 82);
        $disk = (string) config('ambassadors.photo.disk', 'public');

        $manager = ImageManager::usingDriver(GdDriver::class);
        $image = $manager->decodeSplFileInfo($file);
        $image->cover($size, $size);
        $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality);

        $path = 'ambassadors/'.$ambassador->id.'.webp';

        Storage::disk($disk)->put($path, $encoded->toString());

        if ($ambassador->photo_path !== null && $ambassador->photo_path !== $path) {
            Storage::disk($disk)->delete($ambassador->photo_path);
        }

        $ambassador->forceFill(['photo_path' => $path])->save();

        return $path;
    }
}
