<?php

namespace App\Helpers;

use App\Models\Notification;
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
    public static function sendEmail($to,$subject,$body,$headers)
    {
        mail($to, $subject, $body,$headers);
    }
    public static function createNotification($userId, $type, $message, $data = null)
    {
        $serializedData = json_encode($data);

        $notification = new Notification();
        $notification->user_id = $userId;
        $notification->message = $message;
        $notification->type = $type;
        $notification->data = $serializedData;
        $notification->save();
    }
}
