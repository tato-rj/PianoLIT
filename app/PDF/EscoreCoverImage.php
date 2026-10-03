<?php

namespace App\PDF;

/** A temporary, cropped copy of a collection image; the stored original is never changed. */
class EscoreCoverImage
{
    public const IMAGE_HEIGHT_RATIO = 408 / 792;
    private $path;

    public static function pageDimensions($paper)
    {
        return $paper === 'a4' ? [595.276, 841.89] : [612, 792];
    }

    public static function fromStorage($path, $paper = null)
    {
        if (!$path) return null;
        $disk = \Storage::disk('public');
        if (!$disk->exists($path)) return null;
        $bytes = $disk->get($path);
        $size = @getimagesizefromstring($bytes);
        if (!$size || $size[0] < 1 || $size[1] < 1 || $size[0] * $size[1] > 40000000) return null;
        $source = @imagecreatefromstring($bytes);
        if (!$source) return null;
        $copy = null;
        $temporary = null;
        try {
            // Fill the bottom panel at the selected paper's ratio without stretching.
            [$pageWidth, $pageHeight] = self::pageDimensions($paper);
            $ratio = $pageWidth / ($pageHeight * self::IMAGE_HEIGHT_RATIO);
            $cropWidth = min($size[0], $size[1] * $ratio);
            $cropHeight = $cropWidth / $ratio;
            $width = max(1, (int) min(1836, $cropWidth));
            $height = max(1, (int) round($width / $ratio));
            $copy = imagecreatetruecolor($width, $height);
            imagefill($copy, 0, 0, imagecolorallocate($copy, 255, 255, 255));
            imagecopyresampled($copy, $source, 0, 0, (int) (($size[0] - $cropWidth) / 2), (int) (($size[1] - $cropHeight) / 2), $width, $height, max(1, (int) $cropWidth), max(1, (int) $cropHeight));
            $temporary = tempnam(sys_get_temp_dir(), 'escore-image-');
            if (!$temporary || !imagejpeg($copy, $temporary, 92)) throw new \RuntimeException('The collection cover image could not be prepared.');
            $image = new self;
            $image->path = $temporary;
            return $image;
        } catch (\Throwable $exception) {
            if ($temporary && is_file($temporary)) unlink($temporary);
            throw $exception;
        } finally {
            imagedestroy($source);
            if ($copy) imagedestroy($copy);
        }
    }

    public function path()
    {
        return $this->path;
    }

    public function delete()
    {
        if (is_file($this->path)) unlink($this->path);
    }
}
