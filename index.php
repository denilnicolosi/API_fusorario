<?php
// php://input restituisce i dati raw (testo), 
//i dati che si riceveranno saranno in formato Json.
$content = file_get_contents("php://input");

$token = getenv('BOTTOKEN');

if($content) {
	
	//decodifica del json in array
    $update = json_decode($content, true);

    if(isset($update['message'])) {
		//catturo tutti gli elementi ricevuti da telegram
        $message = $update['message'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        $from_id = $message['from']['id'];
        $text = $message['text'];
		
		//elaborazione della risposta
		$message_to_send = "Hai scritto '{$text}'";
		
		//invio del messaggio di risposta
		sendMessage($token, $chat_id, $message_to_send);
      
    }
}

//funzione per inviare un messaggio di risposta all'utente
function sendMessage($token, $chatId, $messageText){
	
		$parameters = array(
            'chat_id' => $chatId,
            'text' => $messageText
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
