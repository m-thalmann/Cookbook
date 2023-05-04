<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class RecipeImage extends BaseModel {
    use HasFactory;

    const IMAGE_DIRECTORY = 'images/recipes';

    protected $fillable = [];

    protected $hidden = ['image_path'];

    protected $appends = ['url'];

    public function getUrlAttribute() {
        return asset(Storage::url($this->image_path));
    }

    public function recipe() {
        return $this->belongsTo(Recipe::class);
    }

    protected static function booted() {
        static::deleted(function (RecipeImage $image) {
            if ($image->image_path !== null) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }

    public static function deleteAllImages() {
        $images = Storage::disk('public')->allFiles(self::IMAGE_DIRECTORY);

        Storage::disk('public')->delete($images);
    }

    public static function pruneImages() {
        $databaseImages = RecipeImage::query()
            ->get('image_path')
            ->pluck('image_path')
            ->toArray();

        $storageImages = Storage::disk('public')->allFiles(
            RecipeImage::IMAGE_DIRECTORY
        );

        $pathToDelete = [];

        foreach ($storageImages as $image) {
            if (!in_array($image, $databaseImages)) {
                $pathToDelete[] = $image;
            }
        }

        $amountToDelete = count($pathToDelete);

        if ($amountToDelete > 0) {
            Storage::disk('public')->delete($pathToDelete);
        }

        return $amountToDelete;
    }
}
