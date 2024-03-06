<?php

namespace App\Helpers;

use App\Models\Notification;
use File;
use Firebase\JWT\JWT;

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
    public static function getMeetingDetals($meetingId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.videosdk.live/v2/rooms/" . $meetingId,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: '.Helper::getVideoSdkToken(),
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    private static function getVideoSdkToken() 
    {
        $api_key = "c8daa48a-bf5e-45bb-80fb-487460b78537";
        $secret_key = "ed51d792758d1132763244870575ed18d80d0e3f099c78748123cf9ed6d2fe3d";
        
        $payload = [
            'apikey' => $api_key,
            'permissions'=> ["allow_join"],
        ];
        
        $token = JWT::encode($payload, $secret_key, 'HS256');
        return $token;
    }
}
