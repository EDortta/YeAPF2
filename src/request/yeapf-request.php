<?php
declare(strict_types=1);
namespace YeAPF;

class Request {
    public static function do(string $url, string $method, array $data = [], array $user = []) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        if (!empty($user)) {
            curl_setopt($ch, CURLOPT_USERPWD, $user['username'] . ':' . $user['password']);
        }

        /**
         * debug curl
         */
        $debugging=false;
        if (is_dir(__DIR__."/logs")) {
            $logFile = __DIR__."/logs/".date("Y-m-d").'.log';
            $fp = fopen($logFile, 'a+');
            curl_setopt($ch, CURLOPT_STDERR, $fp);
            $debugging=true;
        }


        $response = curl_exec($ch);
        curl_close($ch);

        if (false == $response) {
            printf("cURL Error: %s\n", curl_error($ch));
        }

        if ($debugging) {
            rewind($fp);
            $log = stream_get_contents($fp);
            fclose($fp);
        }


        return $response;
    }
}