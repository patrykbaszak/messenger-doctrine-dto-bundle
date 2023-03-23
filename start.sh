#!/bin/bash

sudo chown -R $USER .

docker-compose --env-file docker/db/.env down --volumes --remove-orphans

docker-compose --env-file docker/db/.env up -d 

rm -rf var/cache

sudo chown -R $USER .

docker-compose exec app composer cache:clear
