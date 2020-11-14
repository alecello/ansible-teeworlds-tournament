#!/bin/bash

rm creds.txt
sqlite3 data/database "SELECT password || ' ' || name FROM players" > creds.txt