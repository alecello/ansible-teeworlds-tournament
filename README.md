# Torneo teeworlds

Work in progress

## Website

Everything is ansible managed, but if you want to develop locally...

Create provisioning/files/nginx-site-files/config.php:

```php
<?php
define("DATABASE_PATH", __DIR__ . '/../../../database.sqlite');
define("CLASSIFICA_UPDATE_SECONDS", 120);
```

Then in this directory:

```bash
sqlite3 database.sqlite 'VACUUM;'
sqlite3 database.sqlite 'CREATE TABLE IF NOT EXISTS players (password text PRIMARY KEY CHECK(length(password) == 15), name text NOT NULL CHECK(length(name) <= 15), email text);';
sqlite3 database.sqlite 'CREATE TABLE IF NOT EXISTS kills (id integer PRIMARY KEY CHECK(id >= 0), time integer NOT NULL CHECK(time > 0), killer text NOT NULL CHECK(length(killer) == 15), killed text NOT NULL CHECK(length(killed) == 15));';
cd provisioning/files/nginx-site-files
php -S 127.0.0.1:8080
```

And the site is served at 127.0.0.1:8080.

Note that the table definitions are located in provisioning/files/teeworlds/database/database.py.
