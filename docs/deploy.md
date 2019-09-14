# Messa online del servizio
Per la messa online del servizio viene utilizzato il servizio di *continuous delivery* [Heroku](http://heroku.com).
Questo è stato configurato tale che esegua un deploy automatico ad ogni modifica effettuata 
in questa repository github nel branch *master*.

## Variabili d'ambiente
Le variabili d'ambiente del servizio sono variabili interne che restano private, evitando di 'scolpire' nel codice
informazioni che non dovrebbero essere pubbliche.
In particolare, le variabili impostate sono:

 * BOTTOKEN (token del bot di telegram)
 * DATABASE_URL (stringa di connessione al database)
 * USERNAME (utente utilizzato per effettuare la richiesta all'API)
 * PASSWORD (password utilizzata per effettuare la richiesta all'API)


## Collegamento del webhook telegram
Per il collegamento dell'api al servizio bot di telegram, e stato impostato il weebook come
illustato nella seguente guida ufficiale [impostare il webhook](https://core.telegram.org/bots/api#setwebhook).

