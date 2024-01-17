<?php

namespace App\Helpers;

use File;

class Helper
{
    public static function saveImageToServer($file,$dir)
    {
        $path = public_path() . $dir;
        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }

        $filename = rand(10000,100000).'_'.time().'_'.$file->getClientOriginalName();
        $file->move($path, $filename);
        $filePath = $dir.$filename;

        return $filePath;
    }

}
