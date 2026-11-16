<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Resizes (max 512px square-bounded) and re-encodes avatars as JPEG with GD.
 */
class AvatarProcessor
{
    public static function store(UploadedFile $file, int $userId): string
    {
        $src = match ($file->getMimeType()) {
            'image/png' => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
            default => imagecreatefromjpeg($file->getRealPath()),
        };
        abort_if($src === false, 422, 'The image could not be processed.');

        [$w, $h] = [imagesx($src), imagesy($src)];
        $side = min($w, $h);
        $dst = imagecreatetruecolor(512, 512);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 512, 512, $side, $side);

        ob_start();
        imagejpeg($dst, null, 85);
        $bytes = (string) ob_get_clean();

        $path = "avatars/{$userId}-".time().'.jpg';
        Storage::disk('public')->put($path, $bytes);

        return $path;
    }
}
