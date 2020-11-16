#!/bin/bash

rm $2
sqlite3 $1 "SELECT password || ' ' || name FROM players" > $2
chmod 600 $2