#!/bin/bash

sudo chown -R $USER .

docker-compose --env-file .env down --volumes --remove-orphans

docker-compose --env-file .env up -d 

rm -rf var/cache

sudo chown -R $USER .

docker-compose exec app composer cache:clear
