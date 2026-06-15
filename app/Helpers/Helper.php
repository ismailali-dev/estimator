<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Exceptions\Handler;

class Helper {

    public static function file_upload(Request $request, string $field_name, string $dir)
    {
        return "storage/app/".$request->file($field_name)->storePubliclyAs(
            $dir,$request->file($field_name)->hashName()
        );
    }

    public static function reqValue($key): string
    {
        if(empty(\request()->toArray()[$key])){
            return "";
        }
        return \request()->toArray()[$key];
    }
    public static function dates_month($month, $year,$format='d-M-Y') {
        $num = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dates_month = array();

        for ($i = 1; $i <= $num; $i++) {
            $mktime = mktime(0, 0, 0, $month, $i, $year);
            $date = date($format, $mktime);
            $dates_month[$i] = $date;
        }

        return $dates_month;
    }


    public static function rangeMonth (): array
    {
        $months = [];
        for ($m=1; $m<=12; $m++) {
            $month = date('F', mktime(0,0,0,$m, 1, date('Y')));
            $months[]= $month;
        }
        return $months;
    }

    public static function getBalance($natureId,$nature){
        $balance = Ledger::where("nature_id",$natureId)->where('nature',$nature)->orderBy('id', 'desc')->get('balance')->first()['balance'];
        return (!empty($balance) ? $balance : 0);
    }

    public static function price($amount): string
    {
        $format = getenv('MIX_APP_PRICE_FORMAT');
        return str_ireplace("#AMOUNT",number_format(floatval($amount)),$format);
    }

    public static function verification_code($userId):string
    {
        $code = mt_rand( 10000, 99999 );
        $user = User::where("id", "!=", $userId)->where("verification_code", "=", $code)->first();
        if (!empty($user)){
            self::verification_code($userId);
        }
        return $code;
    }

    public static function reset_code($email):string
    {
        $code = mt_rand( 10000, 99999 );
        $checkCode = DB::table("password_resets")->where("email", "=", $email)->where("token", "=", $code)->first();
        if (!empty($checkCode)){
            self::reset_code($email);
        }
        return $code;
    }

    public static function sendFCM($participants, $message):JsonResponse
    {
        //$participants = [$participants[0]];
        try{
            $url = "https://fcm.googleapis.com/fcm/send";
            $postData = [];
            if (count($participants) > 1){
                $postData["registration_ids"] = $participants;
            }else{
                $postData["to"] = $participants[0];
            }
            $postData["notification"] = $message;
            $http = Http::withToken(env('FCM_TOKEN'), "key=");
            $response = $http->post($url, $postData);
            if ($response->successful()){
                return response()->json(["status"=>$response->successful(), "data"=>$response->json()], 200);
            }else{
                throw new \Exception("Post request failed with status code:".$response->status());
            }
        }
        catch (\Exception $e){
            return response()->json(["status"=>false, 'error' => $e->getMessage()], 200);
        }
    }









}

?>
