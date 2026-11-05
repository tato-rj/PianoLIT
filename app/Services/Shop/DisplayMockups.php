<?php

namespace App\Services\Shop;

use App\Merchandise\{MerchandiseImage, MerchandiseProduct};
use App\Shop\{eBook, eScore};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;

/** Curated promotional artwork, independent of product galleries and provider refreshes. */
class DisplayMockups
{
    public static function version(Model $product): string
    {
        return hash('sha256', (string) $product->display_mockup_path);
    }

    private static function ownsPath(?string $path): bool
    {
        return is_string($path) && preg_match('~\Ashop/display-mockups/[a-f0-9-]{36}\.(?:jpg|png)\z~D', $path) === 1;
    }

    public static function url(?string $path): ?string
    {
        return self::ownsPath($path) ? (new MerchandiseImage(['disk' => 'public', 'path' => $path]))->url : null;
    }

    public function discard(?string $path): void
    {
        if (self::ownsPath($path)) Storage::disk('public')->delete($path);
    }

    public function save(Model $product, ?UploadedFile $file, string $version, ?int $revision = null): void
    {
        abort_unless(in_array(get_class($product), [MerchandiseProduct::class, eBook::class, eScore::class], true), 404);
        $images = app(MerchandiseImages::class);
        $asset = $file ? $images->fromDisplayUpload($file, $product->name ?? $product->title) : null;
        try {
            $previous = DB::transaction(function () use ($product, $asset, $version, $revision) {
                $locked = $product->newQuery()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
                if (!hash_equals(self::version($locked), $version)
                    || ($locked instanceof MerchandiseProduct && $locked->revision !== $revision)) {
                    throw ValidationException::withMessages(['display_mockup' => 'This product changed in another tab. Reload the page and try again.'])->errorBag('displayMockup');
                }
                $previous = $locked->display_mockup_path;
                $locked->forceFill(['display_mockup_path' => $asset['path'] ?? null]);
                if ($locked instanceof MerchandiseProduct) $locked->revision++;
                $locked->save();
                return $previous;
            });
        } catch (\Throwable $exception) {
            if ($asset) $images->discard([$asset]);
            throw $exception;
        }
        DB::afterCommit(fn () => $this->discard($previous));
    }
}
