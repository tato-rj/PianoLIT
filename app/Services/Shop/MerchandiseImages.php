<?php

namespace App\Services\Shop;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Http, Storage};
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

class MerchandiseImages
{
    const MAX_BYTES = 10 * 1024 * 1024;
    const MAX_IMAGES = 40;

    public function allowedUrl($url): bool
    {
        if (!is_string($url) || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) return false;
        $parts = parse_url($url);
        return ($parts['scheme'] ?? '') === 'https'
            && in_array(strtolower($parts['host'] ?? ''), ['files.cdn.printful.com', 'cdn.printful.com'], true)
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment'])
            && (!isset($parts['port']) || $parts['port'] === 443);
    }

    public function fromPrintful(string $url, string $alt, int $timeout = 5): array
    {
        if (!$this->allowedUrl($url)) throw new PrintfulException('This mockup URL is not supported.');
        try {
            // Image downloads never receive the API token and never follow redirects.
            $response = Http::withOptions(['allow_redirects' => false, 'stream' => true, 'connect_timeout' => 5, 'read_timeout' => 5])
                ->timeout($timeout)->get($url);
            if (!$response->successful() || (int) $response->header('Content-Length') > self::MAX_BYTES) {
                throw new \RuntimeException('Invalid mockup response');
            }
            $stream = $response->toPsrResponse()->getBody();
            $bytes = '';
            try {
                while (!$stream->eof() && strlen($bytes) <= self::MAX_BYTES) {
                    $chunk = $stream->read(min(65536, self::MAX_BYTES + 1 - strlen($bytes)));
                    if ($chunk === '' && !$stream->eof()) throw new \RuntimeException('Incomplete mockup response');
                    $bytes .= $chunk;
                }
            } finally {
                $stream->close();
            }
        } catch (\Throwable $exception) {
            throw new PrintfulException('A Printful mockup could not be downloaded.');
        }
        return $this->store($bytes, $alt, 'printful', hash('sha256', $url));
    }

    public function fromUpload(UploadedFile $file, string $alt): array
    {
        return $this->store(file_get_contents($file->getRealPath()), $alt, 'upload');
    }

    private function store(string $bytes, string $alt, string $source, ?string $sourceKey = null): array
    {
        $size = @getimagesizefromstring($bytes);
        if (strlen($bytes) > self::MAX_BYTES || !$size || !in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || $size[0] > 6000 || $size[1] > 6000 || $size[0] * $size[1] > 24000000) {
            throw new PrintfulException('Use a JPG, PNG or WebP image up to 10 MB and 6,000 pixels per side (24 megapixels).');
        }
        try {
            $image = Image::make($bytes)->orientate();
            // Re-encode raster data; keep transparency for sticker mockups.
            $extension = $size['mime'] === 'image/jpeg' ? 'jpg' : 'png';
            $encoded = (string) $image->encode($extension, 88);
            $width = $image->width();
            $height = $image->height();
            $image->destroy();
        } catch (\Throwable $exception) {
            throw new PrintfulException('This image could not be processed. Choose another JPG, PNG or WebP file.');
        }
        if (strlen($encoded) > self::MAX_BYTES) throw new PrintfulException('The processed image exceeds 10 MB. Choose a smaller image.');
        $path = 'shop/merchandise/'.Str::uuid().'.'.$extension;
        $disk = 'public';
        try {
            if (!Storage::disk($disk)->put($path, $encoded)) throw new \RuntimeException('Image storage failed');
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw new PrintfulException('The image could not be saved. Try again.');
        }
        return compact('disk', 'path', 'source', 'width', 'height', 'alt')
            + ['checksum' => hash('sha256', $encoded), 'source_key' => $sourceKey];
    }

    public function discard(array $assets): void
    {
        foreach ($assets as $asset) Storage::disk($asset['disk'])->delete($asset['path']);
    }
}
