<?php

namespace App\Support;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class UserPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        if (!$media->model) {
            // Jika tidak ada, simpan di folder 'orphaned' agar tidak error
            return 'orphaned/' . $media->id . '/';
        }
        // Struktur: users/nama-user-slug-id/
        $userName = Str::slug($media->model->name);
        return 'users/' . $userName . '-' . $media->model->id . '/';
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
