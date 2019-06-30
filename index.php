<?php
// php://input restituisce i dati raw (testo), 
//i dati che si riceveranno saranno in formato Json.
$content = file_get_contents("php://input");

$token = getenv('BOTTOKEN');

if($content) {

	//connessione con database
	if(!$conn = pg_connect(getenv("DATABASE_URL"))){
		error_log("Connessione al database fallita");
	}else{
		error_log("Connessione al database riuscita");
	}
	
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
		$message_to_send = elaborateMessage($conn, $chat_id, $text);
		
		//invio del messaggio di risposta
		sendMessage($token, $chat_id, $message_to_send);
       
    }
}

//funzione per elaborare la risposta
function elaborateMessage($conn, $chat_id, $text){
	
	//stampa di debug della tabella del database	
	$arr = pg_fetch_all(pg_query($conn, "SELECT * FROM user_id;"));
	error_log("db: ".print_r($arr,1));
	
	$status=getChatStatus($conn, $chat_id);
	
	switch($text){
		case "/start":
			$response="Benvenuto in timezone bot!\n\n" .
				elaborateMessage($conn, $chat_id,"/help");
			deleteChat($conn, $chat_id);	
			break;
		case "/help":
			$response = helpMessage();
			deleteChat($conn, $chat_id);
			break;
		case "/list_timezone":
			$response=getTimeZoneList($chat_id);				
			deleteChat($conn, $chat_id);		
			break;		
		case "/timezone_from_an_ip":
			$response="Inserire indirizzo ip "
					  ."(ad esempio 151.23.42.55): ";
			setChatStatus($conn, $chat_id, 1);
			break;
		case "/timezone_from_location":
			$response="Inserisci una area tra le seguenti:";
			setChatStatus($conn, $chat_id, 2);
			break;	
		default:
			if($status==0){
				$response="Comando non trovato.\n\n" .
					elaborateMessage($conn, $chat_id,"/help");
				break;
			}
	}
	if($response==null){	
		switch($status){
			case 1:
				$response="ricerca indirizzo {$text}";
				deleteChat($conn, $chat_id);
				break;
			case 2:
				$response="Inserisci una località tra le seguenti:{$text}";
				setChatStatus($conn, $chat_id, 3);
				break;
			case 3:
				$response="fuso orario della località:{$text}";				
				deleteChat($conn, $chat_id);
				break;
		}
	}
	
	return $response;
}

//funzione per controllare se esite lo stato della chat e ne ritorna il valore
function getChatStatus($conn, $chat_id){
	$res=pg_query($conn, "SELECT status FROM user_id WHERE chat_id={$chat_id};");
	$arr = pg_fetch_all($res);
	if($res && pg_num_rows($res)>0){
		return $arr[0]["status"];
	}else{
		return 0;
	}
}

//funzione per settare lo stato della chat	
function setChatStatus($conn, $chat_id, $status){
    
    $query="INSERT INTO user_id(chat_id,status)"
          ." VALUES({$chat_id},{$status})"
		  ." ON CONFLICT(chat_id) DO"
		  ." UPDATE"
          ." SET status = {$status}";
          
	$res = pg_query($conn, $query);
	if (!$res) {
		error_log("Errore inserimento {$chat_id} ");
		error_log($query);
	}
}

//funzione per eliminare lo stato della chat
function deleteChat($conn, $chat_id){
	$query="DELETE FROM user_id "
          ." WHERE chat_id={$chat_id}";
		  
	$res = pg_query($conn, $query);
	if (!$res) {
		error_log("Errore cancellazione {$chat_id} ");
		error_log($query);
	}
}

//funzione per stampare messaggio di aiuto
function helpMessage(){
	return "Possibili comandi: \n" .
			"/list_timezone -> stampa la lista di tutte le zone di fuso orario. \n" .
			"/timezone_from_an_ip -> stampa il fuso orario di una zona dato l'ip. \n" .
			"/timezone_from_location -> stampa il fuso orario di una zona data. \n";
}

//funzione per inviare un messaggio di risposta all'utente
function sendMessage($token, $chatId, $messageText){
	
		$parameters = array(
            'chat_id' => $chatId,
            'text' => $messageText,
            'parse_mode' => "html"
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

//funzione per inviare la lista delle timezone dalla api
function getTimeZoneList($chat_id){
		global $token;
		
	    $url = "http://worldtimeapi.org/api/timezone";
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPGET, true);
        $response = curl_exec($handle);
        $timezone=json_decode($response, true);
        
        $areas=array();
        
        foreach($timezone as $t){
			array_push($areas, substr($t,0,strpos($t,"/")));
		}
        
        $areas= array_unique($areas);
        
        foreach($areas as $area){
			if(strlen($area)>0){
				$zone=array();
				
				foreach($timezone as $t){
					$pos=strpos($t, $area);
					if(!($pos===false) && $pos < strpos($t,"/")){ 
						array_push($zone, $t);
					}
				}
				
				sendMessage($token, $chat_id,
				 "<b>".$area.":</b> \n". implode("\n", $zone));
			}
        }
        
        return "";       
}


?>



