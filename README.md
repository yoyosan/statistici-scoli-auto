Despre
======

Aceasta aplicatie afiseaza promovabilitatea la primul examen al elevilor scolilor de soferi din Romania, in functie de judet.

Instalare
=========

Programe necesare:
* php
* composer

Pregatiti baza de date:
```
cp data/statistici.sample.sqlite data/statistici.sqlite
```

Rulati urmatoarele comenzi:

```
composer install
php -S localhost:8000
```

**Atentie** php.ini trebuie sa contina urmatoarele setari:

```
max_execution_time = 0
memory_limit = 1024M
```

Cum o folosesc?
===============

Accesati in browserul dvs adresa http://localhost:8000. Implicit, vor fi afisate toate scolile pentru Bucuresti.

Pentru a afisa statisticile si pentru alte judete, puteti accesa http://localhost:8000/<judet>.

Drept exemplu, folositi URLul http://localhost:8000/Iasi pentru a afisa statisticile scolilor  de soferi din judetul Iasi.

Sursa statiscilor: http://www.drpciv.ro/info-portal/displayStatistics.do.
