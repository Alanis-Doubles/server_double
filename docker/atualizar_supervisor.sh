#!/bin/bash

# Loop infinito
while true; do
    supervisorctl reread
    supervisorctl update

    # Esperar 10 segundos
    sleep 10
done