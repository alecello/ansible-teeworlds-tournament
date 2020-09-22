#!/usr/bin/env python3
import sqlite3, os, time, signal

# SIGINT and SIGTERM tell the program to terminate
# SIGHUP tells the program to repopulate the tables
def handler(sig, frame):
    global readl

    if sig in (signal.SIGINT.value, signal.SIGTERM.value):
        readl = False
    elif sig == signal.SIGHUP.value:
        reread = True


signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)
signal.signal(signal.SIGHUP, handler)

playerstable =  ''' CREATE TABLE IF NOT EXISTS players (
                        password text PRIMARY KEY CHECK(length(password) == 15),
                        name text NOT NULL CHECK(length(name) <= 15)
                    );
                '''

killstable =    ''' CREATE TABLE IF NOT EXISTS kills (
                        id integer PRIMARY KEY CHECK(id >= 0),
                        time integer NOT NULL CHECK(time > 0),
                        killer text NOT NULL CHECK(length(killer) == 15),
                        killed text NOT NULL CHECK(length(killed) == 15)
                        );
                '''

index = 0       # Stores number of processed lines (and index to next line)
count = 0       # Stores index of next entry in datbase
flush = False   # Wether we should reload entire database on the next execution
readl = True    # Wether to continue reading the file
lastl = None    # Last line

log = open('kills.log', 'r')
database = sqlite3.connect('data/database')
cursor = database.cursor()

# Create tables if they don't exist
# To avoid duplicates, dump kills table and then repopulate it
cursor.execute(playerstable)
cursor.execute(killstable)
cursor.execute('DELETE FROM kills;')
database.commit()

while(readl):
    lines = log.readlines()

    # Catch if the file got smaller. Also try to catch wether the same number of lines got deleted and added by looking
    # at the last line.
    if(flush or index >= len(lines) or (lastl and lastl != lines[index - 1].replace('\n', ''))):
        flush = False

        cursor.execute('DELETE FROM kills;')
        database.commit()
        index = count = 0

    for line in lines[index:]:
        timestamp, victim, killer = (l.replace('\n', '') for l in line.split(' '))

        try:
            cursor.execute(f'INSERT INTO kills (id, time, killer, killed) VALUES ({count}, "{timestamp}", "{killer}", "{victim}");')
            count += 1
        except sqlite3.IntegrityError as error:
            if(str(error).startswith('CHECK constraint failed')):
                print(f'ERROR: Got invalid data from kills file: "{timestamp}: {killer}" -> "{victim}"')
            else:
                raise error

        index += 1

    database.commit()
    log.seek(0)

    if(index > 0):
        lastl = lines[index - 1]
    
    slept = 0
    size = os.path.getsize('kills.log')
    while(readl and not flush and slept < 60 and os.path.getsize('kills.log') in (0, size)):
        time.sleep(5)
        slept += 1

database.commit()
database.close()
log.close()
exit(0)