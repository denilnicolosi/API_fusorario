# Dati e servizi esterni
Come servizio esterno viene utilizzata la openAPI [World time API](http://worldtimeapi.org/).

In particolare, vengono effettuate le seguenti richieste:

## Lista delle zone di fuso orario
Per catturare la lista delle zone di fuso orario, viene effettuata la seguente richiesta HTTP con il metodo GET :

```
http://worldtimeapi.org/api/timezone
```

Che restituisce un JSON contenente tutte le zone.

## Fuso orario da zona
Per richiedere il fuso orario di una zona, viene effettuata la richiesta HTTP con il metodo GET :

```
http://worldtimeapi.org/api/timezone/{location}
```

Che restituisce un JSON contentente le informazioni fuso orario con il seguente [schema](http://worldtimeapi.org/pages/schema).

## Fuso orario da indirizzo ip
Per richiedere il fuso orario da un indirizzo ip, viene effettuata la richiesta HTTP con il metodo GET :

```
http://worldtimeapi.org/api/ip/{ip}
```

Che restituisce un JSON contentente le informazioni fuso orario con il seguente [schema](http://worldtimeapi.org/pages/schema).
