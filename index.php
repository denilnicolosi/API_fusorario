<?php
$content = file_get_contents("php://input");

$token = getenv('BOTTOKEN');
echo $token;

if($content) {
    $update = json_decode($content, true);

    if(isset($update['message'])) {
        $message = $update['message'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        $from_id = $message['from']['id'];

        $text = $message['text'];

        $parameters = array(
            'chat_id' => $chat_id,
            'text' => "You wrote '{$text}'"
        );

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $query_string = http_build_query($parameters);
        if(!empty($query_string)) {
            $url .= '?' . $query_string;
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        $response = curl_exec($handle);
    }
}
