# Guida all'uso

## Guida all'uso di una conversazione telegram
Per avviare una conversazione telegram con il bot, aggiungerlo cercando '*fusorario_bot*' o tramite il seguente link [fusorario_bot](https://t.me/fusorario_bot).

### Avvio del bot
Per avviare il bot, basterà premere il pulsante "Avvia" per essere accolti dal messaggio di benvenuto.
Per visualizzare i comandi, si dovrà premere il pulsante "/" o inziare un messaggio con quest'ultimo carattere.
<p align="center">
  <img src='img/bot_start.jpg' height='450' />
  <img src='img/bot_command.jpg' height='450' />
</p>


### Stampa della lista delle zone di fuso orario
Per stampare una lista delle zone di fuso orario, si dovrà digitare il comando **/list_timezone**.
Il bot visualizzerà tutte le zone, divise in più messaggi per area.
<p align="center">
  <img src='img/bot_list.jpg' height='450'/>
</p>

### Stampa del fuso orario di una zona
Per visualizzare le informazioni di fuso orario di una determinata zona, si dovrà digitare il comando **/timezone_from_location**.
Si dovrà inserire prima un'area fra quelle elencate, e poi la località.
Il bot visualizzerà tutti i dati relativi al fuso della zona specificata.
<p align="center">
  <img src='img/bot_zone1.jpg' height='450' />
  <img src='img/bot_zone2.jpg' height='450' />
</p>

### Stampa del fuso orario da un indirizzo ip
Per visualizzare le informazioni di fuso orario di una determinata zona ricavata dalla geolocalizzazione di un indirizzo ip, si dovrà digitare il comando **/timezone_from_an_ip**.
Il bot visualizzerà tutti i dati relativi al fuso della zona dell'indirizzo di rete.
<p align="center">
  <img src='img/bot_ip.jpg' height='450'/>
</p>


## Guida all'uso del client c#
Il client come applicazione desktop per windows ha una interfaccia banale e intuitiva.
Dalla finestra principale si potrà inserire o un indirizzo ip, o una zona di fuso, e fare click sul relativo pulsante.
Le informazioni di fuso orario compariranno nell'area di testo sottostante.
<p align="center">
  <img src='img/client_wpf.jpg' height='300'/>
</p>


