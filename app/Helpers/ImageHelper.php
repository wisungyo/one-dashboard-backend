<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ImageHelper
{
    /**
     * Default file storage
     * 'public' or 's3'
     */
    private static $disk = 'public';

    /**
     * Directory or Images / Files
     */
    public static $dir = '';

    /**
     * Root Directory
     */
    public static $rootDir = '';

    /**
     * Resize image from given url and size
     * Automatically upload thumbnail
     *
     * @param  string  $file
     * @param  int  $crop
     * @return string S3 Path
     */
    public static function resize($file, ?int $width = null, ?int $height = null, $crop = true)
    {
        $file_name = (! is_string($file) && ! empty($file->getClientOriginalName())) ? $file->getClientOriginalName() : basename($file);
        $img = is_string($file) ? Image::make(public_path($file)) : Image::make($file);
        $old_width = $img->width();
        $old_height = $img->height();

        /* Resize Image */
        if ($crop) {
            if (! empty($width) && ! empty($height)) {
                $img = $img->fit($width, $height);
            } elseif (! empty($width) && empty($height)) {
                $img = $img->fit($width, $old_height);
            } elseif (! empty($height) && empty($width)) {
                $img = $img->fit($old_width, $height);
            }
        } else {
            if (! empty($width) && ! empty($height)) {
                $img = $img->resize($width, $height);
            } elseif (! empty($width) && empty($height)) {
                $img = $img->widen($width);
            } elseif (! empty($height) && empty($width)) {
                $img = $img->heighten($height);
            }
        }

        return self::putFile($img, $file_name);
    }

    /**
     * Convert Image
     *
     * @param  image|string  $file
     * @return string S3 Path
     */
    public static function convert($file, ?int $size = null)
    {
        $file_name = (! is_string($file) && ! empty($file->getClientOriginalName())) ? $file->getClientOriginalName() : basename($file);
        $img = is_string($file) ? Image::make(public_path($file)) : Image::make($file);
        $old_width = $img->width();
        $old_height = $img->height();

        if ($old_width > $old_height) {
            $img = $img->widen($size);
        } else {
            $img = $img->heighten($size);
        }

        return self::putFile($img, $file_name);
    }

    /**
     * Upload description
     *
     * @param  image|string  $file
     * @return object
     **/
    public static function upload($file)
    {
        $file_name = (! empty($file->getClientOriginalName())) ? $file->getClientOriginalName() : basename($file);
        $img = Image::make($file);

        return self::putFile($img, $file_name);
    }

    /**
     * Put a file into S3
     *
     * @param  object  $img
     * @return array|bool
     */
    public static function putFile($img, ?string $file_name = null)
    {
        $rand = \Str::random(rand(10, 50)).time();
        $key = sha1($rand);
        $file_name = str_replace(' ', '-', $file_name);
        $full_path = date('Y/m/d').'/'.$key.'/'.$file_name;
        $img = self::encode($img, $file_name);
        $full_path = (! empty(self::$dir)) ? self::$dir.'/'.$full_path : $full_path;
        $full_path = (! empty(self::$rootDir)) ? self::$rootDir.'/'.$full_path : $full_path;

        $isUploaded = Storage::disk(self::$disk)->put($full_path, $img->encoded, 'public');
        if (! $isUploaded) {
            return $isUploaded;
        }

        // $pathFinal = Storage::disk(self::$disk)->path($full_path);
        // if ($pathFinal) {
        //     self::removeExifMetadata($pathFinal);
        // }

        return [
            'size' => strlen($img->encoded),
            'width' => $img->width(),
            'height' => $img->height(),
            'mime_type' => $img->mime(),
            'file_name' => $file_name,
            'path' => $full_path,
            'url' => self::getUrl($full_path),
        ];
    }

    public static function getUrl($path)
    {
        return Storage::disk(self::$disk)->url($path);
    }

    /**
     * Delete file from S3
     *
     * @return bool
     */
    public static function delete(string $path)
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        return false;
    }

    /**
     * Set Disk
     *
     * @param self
     */
    public static function setDisk(string $disk)
    {
        self::$disk = $disk;
    }

    /**
     * Set Directory
     *
     * @param self
     */
    public static function setDir(string $dir)
    {
        self::$dir = $dir;
    }

    /**
     * Set Root Directory
     *
     * @param self
     */
    public static function setRootDir(string $dir)
    {
        self::$rootDir = $dir;
    }

    /**
     * Remove exif metadata
     *
     * @param  binary|string  $image
     * @return binary
     */
    private static function removeExifMetadata($image)
    {
        if (extension_loaded('imagick')) {
            $img = new \Imagick($image);
            $img->stripImage();
            $profiles = $img->getImageProfiles('icc', true);
            if (! empty($profiles)) {
                $img->profileImage('icc', $profiles['icc']);
            }

            return $img->writeImage($image);
        } else {
            if ($image) {
                $img = @imagecreatefromjpeg($image);

                return imagejpeg($img, $image, 100);
            }
        }

        return false;
    }

    /**
     * Encode Image by extension
     *
     * @param  binary|string  $image
     * @param  string  $file_name
     * @return binary
     */
    private static function encode($image, $file_name)
    {
        $extension = explode('.', $file_name)[1];
        $image->encode($extension, 90);

        return $image;
    }
}
