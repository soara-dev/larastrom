<?php

if (!function_exists('setResponse')) {
    function setResponse($message, $data = [], $code = 200)
    {
        return response()->json(
            [
                'message' => $message,
                'result' => $data
            ],
            $code
        );
    }
}
