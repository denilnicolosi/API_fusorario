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
		$message_to_send = elaborateMessage($chat_id, $text);
		
		//invio del messaggio di risposta
		sendMessage($token, $chat_id, $message_to_send);
      
    }
}

//funzione per elaborare la risposta
function elaborateMessage($chat_id, $text){
		
	switch($text){
		case "/start":
			$response="Benvenuto in timezone bot!" + 
				elaborateMessage($chat_id,"/help");
			break;
		case "/help":
			$response = helpMessage();
			break;
		case "/list_timezone":
			$response="list time zone";
			break;
		case "/timezone_from_an_ip":
			$response="time zone from ip";
			break;
		case "/timezone_from_location":
			$response="timezone from location";
			break;	
		case default:
			$response="Comando non trovato.\n\n" +
				 elaborateMessage($chat_id,"/help");
			break;
	}
	
	return $response;
}

//funzione per stampare messaggio di aiuto
function helpMessage(){
	return "Possibili comandi: \n" +
			"/list_timezone -> stampa la lista di tutte le zone di fuso orario. \n" +
			"/timezone_from_an_ip -> stampa il fuso orario di una zona dato l'ip. \n" +
			"/timezone_from_location -> stampa il fuso orario di una zona data. \n"
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
