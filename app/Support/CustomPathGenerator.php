<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;
use Modules\Product\Entities\Product; // <-- 1. Tambahkan ini
use App\Models\User; // <-- 2. Tambahkan ini

class CustomPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $model = $media->model;
        $path = '';

        // 3. Logika untuk menentukan path berdasarkan jenis model
        switch (get_class($model)) {
            case Product::class:
                // Struktur: products/nama-produk-slug/
                $productName = Str::slug($model->product_name);
                $path = 'products/' . $productName . '/';
                break;

            case User::class:
                // Struktur: users/nama-user-slug-id/
                $userName = Str::slug($model->name);
                $path = 'users/' . $userName . '-' . $model->id . '/';
                break;

            // Anda bisa tambahkan case lain di sini untuk model yang berbeda
            // default:
            //     // Fallback ke struktur ID media jika model tidak dikenali
            //     return $media->id . '/';
        }

        return $path;
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive-images/';
    }
}
