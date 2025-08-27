<?php

namespace App\Http\Responses\V1;
class ApiResponse{
    public static function success($message="successs",$statusCode=200,$data=[]){
        return response()->json([
            "message"=>$message,
            'statusCode'=>$statusCode,
            'error'=>false,
            'data'=>$data
        ],$statusCode);
    }
    public static function error($message="error",$statusCode=500,$data=[]){
        return response()->json([
            "message"=>$message,
            'statusCode'=>$statusCode,
            'error'=>true,
            'data'=>$data
        ],$statusCode);
    }
}