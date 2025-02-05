<?php

namespace App\Traits;

trait ApiResponse
{
    //
    protected function successResponse($meta = null,$message = null,$data = [], $code = 200)
    {
        return response()->json([
            'status'=> 'Success',
            'meta'=> $meta,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], $code);
    }

    protected function errorResponse($meta=null, $message = null,$data=[],$code=200)
    {
        return response()->json([
            'status'=> false,
            'meta'=> $meta,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ], $code);
    }
}
