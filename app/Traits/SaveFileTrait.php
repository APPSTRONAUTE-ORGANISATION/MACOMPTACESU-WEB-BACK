<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait SaveFileTrait
{
    public function save_file($file, string $folder_name)
    {
        if ($file == null) {
            return null;
        }

        $fileExtension = $file->extension();

        return $file->storeAs($folder_name, Str::random(20) . '.' . $fileExtension, 'public');
    }

    public function save_file_base64(string $base64, string $folder_name)
    {
        $data = explode(',', $base64);
        $fileExtension = explode(';', explode('/', $data[0])[1])[0];

        $file_name = Str::random(20) . '.' . $fileExtension;

        $path = $folder_name . DIRECTORY_SEPARATOR . $file_name;

        Storage::disk('public')->put($path, base64_decode($data[1]));

        return $folder_name . '/' . $file_name;
    }

    public function delete_file($path)
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
        return unlink(storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $path));
    }
}
