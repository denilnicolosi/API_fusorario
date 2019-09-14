# Documentazione API
La documentazione API è conforme allo standard Open API ed è stata generata dal tool online [Swagger](https://swagger.io/). 
Inoltre, è stata prodotta anche una pagina html di documentazione, consultabile presso la pagina iniziale di [api-fusorario](api-fusorario.herokuapp.com)
o in alternativa presso [Swagger](https://app.swaggerhub.com/apis/Denil/api-fusorario/1.0.1).

----------------------------------------------------------

## Telegram
Per la gestione del bot telegram, l'api attende richieste POST sul path `/telegram`.
Per inviare i messaggi di risposta, il servizio effettua una richiesta POST a
```
https://api.telegram.org/bot{token}/sendMessage
```
dove con {token} si intende il token restituito dal BotFather in fase di creazione del bot.

La richiestra avrà i parametri:
 * chat_id (id della chat a cui inviare il messaggio)
 * text (testo del messaggio)

----------------------------------------------------------

## Richieste accettate dall'API
Le richieste elaborate dall'API si articolano in due path:
 * `api-fusorario.herokuapp.com/timezone`
 * `api-fusorario.herokuapp.com/ip`
 
e devono essere effettuate con il metodo GET.

A tutte le richieste, l'API risponderà con gli HTTP response status codes: 

| Status code | Descrizione |
| :---: | ------------- |
| `200` | Risposta OK |
| `400` | Richiesta errata |
| `401` | Autenticazione fallita |
| `503` | Servizio non disponibile |

### Fuso orario da zona
Data una zona formata da {area}/{localita} viene dato il fuso orario. Se non viene specificata una zona, viene stampata la lista delle zone.
La zona desiderata deve essere specificata tramite parametro stringa.

#### Esempi
Effettuando una richiesta GET all'indirizzo
``` 
api-fusorario.herokuapp.com/timezone
```
Si avra come output un JSON contenente tutte le località,
mentre effettuando una richiesta GET al medisimo indirizzo ma con località specificata, come
```
api-fusorario.herokuapp.com/timezone?timezone=Europe/Rome
```
si otterrà il seguente output JSON:
```
{
    "week_number":36,
    "day_of_year":251,
    "day_of_week":0,
    "utc_offset":"+02:00",
    "date":"2019-09-08",
    "time":"15:00:05",
    "timezone":"Europe\/Rome"
}
```

### Fuso orario da indirizzo ip
Dato un indirizzo ip, viene geocalizzato e ne viene dato il fuso orario. In caso non sia specificato un indirizzo ip, viene utilizzato l'indirizzo del client che effettua la richiesta.
L'indirizzo ip deve essere specificato tramite parametro stringa.

#### Esempi
Effettuando una richiesta GET all'indirizzo
``` 
api-fusorario.herokuapp.com/ip
```
Si avra come output un JSON le informazioni sul fuso orario del ip del client, come 
```
{
    "ip":"151.76.178.40",
    "week_number":36,
    "day_of_year":251,
    "day_of_week":0,
    "utc_offset":"+02:00",
    "date":"2019-09-08",
    "time":"17:17:26",
    "timezone":"Europe\/Rome"
}
```
mentre effettuando una richiesta GET al medisimo indirizzo ma con ip specificato, come
```
api-fusorario.herokuapp.com/ip?ip=12.123.43.12
```
si otterrà il medesimo output soprariportato, ma dell'indirizzo da parametro.

----------------------------------------------------------

## Schemi di risposte JSON
Gli schemi di risposta JSON sono i seguenti:

### Lista di zone
In caso di una risposta con una lista di zone, l'API risponderà con il seguente schema:
```
{
    area :    string[]
    Area di fuso orario
}
```

In caso di una risposta con un fuso orario da zona, l'API risponderà con il seguente schema:
```
{
    week_number :    integer
    Numero della settimana dell'anno

    day_of_year	:    integer
    Numero del giorno dell'anno

    day_of_week	:    integer
    Numero del giorno della settimana

    utc_offset :    string
    Scostamento dal formato UTC

    date :    string
    Data

    time :    string
    Orario

    timezone :    string
    Zona di fuso orario
}
```

In caso di una risposta con un fuso orario da indirizzo ip, l'API risponderà con il seguente schema:
```
{
    ip :    string
    Indirizzo ip richiesto

    week_number :    integer
    Numero della settimana dell'anno

    day_of_year	:    integer
    Numero del giorno dell'anno

    day_of_week	:    integer
    Numero del giorno della settimana

    utc_offset :    string
    Scostamento dal formato UTC

    date :    string
    Data

    time :    string
    Orario

    timezone :    string
    Zona di fuso orario
}
```

