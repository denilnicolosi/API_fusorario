<?php
set_include_path('/app');

//stampe di debug in console
error_log("server: ".print_r($_SERVER,1));
error_log("post: ".print_r($_POST,1));
error_log("get: ".print_r($_GET,1));
$request = $_SERVER['REQUEST_URI'];
error_log("request: ".$request);

//connessione con database
if(!$conn = pg_connect(getenv("DATABASE_URL"))){
	error_log("Connessione al database fallita");
	http_response_code(503);
	echo "503 Service unavailable";
	exit;
}else{
	error_log("Connessione al database riuscita");
}


if($_SERVER['REQUEST_METHOD'] === "GET" && $request === "/"){
	//apertura documentazione open api in GET su /
	echo file_get_contents("OpenAPI/openAPI.html");
}
else if(strpos($request, "/ip") !== false){
	authenticate($conn);	
	//gestione del metodo get per ip
	if(isset($_GET['ip'])){
		header('Content-type: application/json');
		echo getJsonTimeZoneFromIp($_GET['ip']);
	}
	//gestione del metodo post per ip
	else if(isset($_POST['ip'])){
		header('Content-type: application/json');
		echo getJsonTimeZoneFromIp($_POST['ip']);
	}
	else if(empty($_GET) && empty($_POST)){
		header('Content-type: application/json');
		echo getJsonTimeZoneFromIp($_SERVER['HTTP_X_FORWARDED_FOR']);
	}	
	else{
		http_response_code(400);
		echo "400 Bad request";
	}
		
}
else if(strpos($request, "/timezone") !== false){
	authenticate($conn);
	//gestione del metodo get per zona
	if(isset($_GET['timezone'])){
		header('Content-type: application/json');
		echo getJsonTimeZoneLocation($_GET['timezone']);
	}
	//gestione del metodo post per zona
	else if(isset($_POST['timezone'])){
		header('Content-type: application/json');
		echo getJsonTimeZoneLocation($_POST['timezone']);
	}
	//gestione della stampa della lista delle zone
	else if(empty($_GET) && empty($_POST)){
		header('Content-type: application/json');
		echo getJsonTimeZoneList();
	}
	else{
		http_response_code(400);
		echo "400 Bad request";
	}
}
else
{
	//gestione del bot telegram
	// php://input restituisce i dati raw (testo), 
	//i dati che si riceveranno saranno in formato Json.
	$content = file_get_contents("php://input");	

	if($content) {

		error_log("content: ".print_r($content,1));	
		
		//decodifica del json in array
		$update = json_decode($content, true);

		if(isset($update['message'])) {
			//catturo tutti gli elementi ricevuti da telegram
			$message = $update['message'];
			$message_id = $message['message_id'];
			$chat_id = $message['chat']['id'];
			$from_id = $message['from']['id'];
			$text = $message['text'];
			
			$token = getenv('BOTTOKEN');
		
			//elaborazione della risposta
			$message_to_send = elaborateMessage($conn, $chat_id, $text);
			
			//invio del messaggio di risposta
			sendMessage($token, $chat_id, $message_to_send);		
		}
		else{
			http_response_code(400);
			echo "400 Bad request";
		}
	}
}

//funzione per elaborare la risposta
function elaborateMessage($conn, $chat_id, $text){
	
	//stampa di debug della tabella del database	
	$arr = pg_fetch_all(pg_query($conn, "SELECT * FROM user_id;"));
	error_log("db: ".print_r($arr,1));
	$response=null;
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
			$response="Inserisci una area tra le seguenti:\n"
			           .getArea();
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
				if(filter_var($text, FILTER_VALIDATE_IP, 
					FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {
					//indirizzo ip valido
					$response=getTimeZoneFromIp($text);
					deleteChat($conn, $chat_id);
				}
				else {
					//indirizzo ip non valido					
					$response="Indirizzo ip non valido! \n" 
					.elaborateMessage($conn, $chat_id,"/timezone_from_an_ip");
				}			
				break;
			case 2:
				$text=formatText($text);
				$location=getLocation($text);
				if(strlen($location)>0){
					setSavedArea($conn, $chat_id, $text);
					$response="Inserisci una località tra le seguenti:\n"
							.$location;
					setChatStatus($conn, $chat_id, 3);
				}else{
					$response="Area non esistente. \n"
					.elaborateMessage($conn, $chat_id,"/timezone_from_location");
				}
				
				break;
			case 3:
				$text=formatText($text);
				$area=getSavedArea($conn, $chat_id);
				$location=$area."/".$text;
				$timezone=getTimeZoneLocation($location);
				if(strcmp($timezone,"error")!=0){
					$response=$timezone;
					deleteChat($conn, $chat_id);
				}else{
					setChatStatus($conn, $chat_id, 2);
					$response="Località non valida! \n"
					.elaborateMessage($conn, $chat_id,"{$area}");
				}				
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
//funzione per formattare l'area e la località
function formatText($text){
	$text=strtolower($text);
	$text=ucwords($text);
	
	//metto maiuscoli i caratteri dopo "_" o "/".
	$offset=0;
	while (($pos = strpos($text, "_", $offset)) !== FALSE) {
        $offset   = $pos + 1;
        $text[$offset] = strtoupper($text[$offset]);
    }
	$offset=0;
	while (($pos = strpos($text, "/", $offset)) !== FALSE) {
        $offset   = $pos + 1;
        $text[$offset] = strtoupper($text[$offset]);
    }
	
	
	return $text;
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
		error_log("Errore inserimento stato {$chat_id} ");
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

//funzione per memorizzare l'area dal database
function setSavedArea($conn, $chat_id, $area){
	 $query="UPDATE user_id"
          ." SET area = '{$area}'"
          ." WHERE chat_id={$chat_id}";
          
	$res = pg_query($conn, $query);
	if (!$res) {
		error_log("Errore inserimento area {$chat_id} ");
		error_log($query);
	}
}

//funzione per catturare l'area dal database
function getSavedArea($conn, $chat_id){
	$res=pg_query($conn, "SELECT area FROM user_id WHERE chat_id={$chat_id};");
	$arr = pg_fetch_all($res);
	if($res && pg_num_rows($res)>0){
		return $arr[0]["area"];
	}else{
		return 0;
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

//funzione per catturare il fuso orario dall'api esterna dato l'ip
function getTimeZoneFromIp($ip){
	
		$url = "http://worldtimeapi.org/api/ip/{$ip}";
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPGET, true);
        $response = curl_exec($handle);
        $timezone=json_decode($response, true);
        
        //composizione della stringa di risposta
        date_default_timezone_set($timezone["timezone"]);
        
        $data=date('Y-m-d', strtotime($timezone["datetime"]));
        $ora=date('H:i:s', strtotime($timezone["datetime"]));
        $response= "<b>Fuso orario dell'indirizzo {$ip} </b>\n"
        ."Numero della settimana : {$timezone["week_number"]} \n"
        ."Giorno dell'anno : {$timezone["day_of_year"]} \n"
		."Giorno della settimana: {$timezone["day_of_week"]} \n"
        ."UTC : {$timezone["utc_offset"]} \n"
        ."Data : {$data} \n"
        ."Ora : {$ora} \n"
        ."Zona di fuso orario : {$timezone["timezone"]}\n";
        
        return $response;
        
}

//funzione per catturare le aree dell'api esterna
function getArea(){
	
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
        $areas = array_unique($areas);
        $reply="";
        foreach($areas as $a){
			if(strlen($a)>0){
				$reply=$reply."\n • ".$a;
			}
        }
                
        return $reply;        
}

//funzione per catturare le località dall'api esterna data l'area
function getLocation($area){
	
	    $url = "http://worldtimeapi.org/api/timezone";
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPGET, true);
        $response = curl_exec($handle);
        $timezone=json_decode($response, true);        
        
        $location=array();        
        foreach($timezone as $t){
			if (strpos($t, $area."/") !== false) {
				array_push( $location,
				 (substr($t,(strpos($t,$area."/")+strlen($area)+1),
				 strlen($t))));
			}
		}        
        $reply="";
        foreach($location as $l){
			if(strlen($l)>0){
				$reply=$reply."\n • ".$l;
			}
        }
            
        return $reply;        
}

//funzione per catturare il fuso orario data l'area e la località
function getTimeZoneLocation($location){
	
	$url = "http://worldtimeapi.org/api/timezone/{$location}";
	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_HTTPGET, true);
	$response = curl_exec($handle);
	$timezone=json_decode($response, true);
	
	if(!isset($timezone['error'])){
	
		//composizione della stringa di risposta
		date_default_timezone_set($timezone["timezone"]);
		
		$data=date('Y-m-d', strtotime($timezone["datetime"]));
		$ora=date('H:i:s', strtotime($timezone["datetime"]));
		$response= "<b>Fuso orario della zona {$location} </b>\n"
		."Numero della settimana : {$timezone["week_number"]} \n"
		."Giorno dell'anno : {$timezone["day_of_year"]} \n"
		."Giorno della settimana: {$timezone["day_of_week"]} \n"
		."UTC : {$timezone["utc_offset"]} \n"
		."Data : {$data} \n"
		."Ora : {$ora} \n"
		."Zona di fuso orario : {$timezone["timezone"]}\n";
		
	}else{
		$response="error";
	}
	
	return $response;
}

//funzione per catturare il fuso orario dall'api esterna dato l'ip
function getJsonTimeZoneFromIp($ip){
	
		$url = "http://worldtimeapi.org/api/ip/{$ip}";
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPGET, true);
        $response = curl_exec($handle);
        $timezone=json_decode($response, true);
              
        if(!isset($timezone['error'])){
			//composizione della stringa di risposta
			date_default_timezone_set($timezone["timezone"]);
			
			$data=date('Y-m-d', strtotime($timezone["datetime"]));
			$ora=date('H:i:s', strtotime($timezone["datetime"]));
				
			$r=array();
			$r['ip']=$ip;
			$r['week_number']= $timezone["week_number"];
			$r['day_of_year']= $timezone["day_of_year"];
			$r['day_of_week']= $timezone["day_of_week"];
			$r['utc_offset']= $timezone["utc_offset"];
			$r['date']=$data;
			$r['time']=$ora;
			$r['timezone']=$timezone["timezone"];
		}else{
			$r="error";
		}
        return json_encode($r);
        
}

//funzione per catturare il fuso orario data l'area e la località
function getJsonTimeZoneLocation($location){
	
	$url = "http://worldtimeapi.org/api/timezone/{$location}";
	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_HTTPGET, true);
	$response = curl_exec($handle);
	$timezone=json_decode($response, true);
	
	if(!isset($timezone["error"])){
		//composizione della stringa di risposta
		date_default_timezone_set($timezone["timezone"]);
		
		$data=date('Y-m-d', strtotime($timezone["datetime"]));
		$ora=date('H:i:s', strtotime($timezone["datetime"]));
			
		$r=array();
		$r['week_number']= $timezone["week_number"];
		$r['day_of_year']= $timezone["day_of_year"];
		$r['day_of_week']= $timezone["day_of_week"];
		$r['utc_offset']= $timezone["utc_offset"];
		$r['date']=$data;
		$r['time']=$ora;
		$r['timezone']=$timezone["timezone"];
	}else{
		$r="error";
	}
	return json_encode($r);
}

//funzione per catturare la lista delle località di fuso orario
function getJsonTimeZoneList(){

	$url = "http://worldtimeapi.org/api/timezone";
	$handle = curl_init($url);
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($handle, CURLOPT_HTTPGET, true);
	$response = curl_exec($handle);

	return $response;
}

//funzione per la gestione dell'autenticazione
function authenticate($conn){

	if (!isset($_SERVER['PHP_AUTH_USER'])) {
		header('WWW-Authenticate: Basic realm="Autenticazione"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Autenticazione fallita';
		exit;
	} else {		
		if(!loginUser($conn)){
			header('HTTP/1.0 401 Unauthorized');
			echo 'Username o password errati.';
			exit;
		}		
	}

}

//funzione per controllare l'utente nel database
function loginUser($conn){

	$username=$_SERVER['PHP_AUTH_USER'];
	$password=$_SERVER['PHP_AUTH_PW'];
	$validation=false;

	$res=pg_query($conn, "SELECT password FROM account WHERE username = '{$username}';");
	$arr = pg_fetch_all($res);
	if($res && pg_num_rows($res) > 0)
		if($password === $arr[0]["password"])
			$validation=true;

	return $validation;

}


?>



