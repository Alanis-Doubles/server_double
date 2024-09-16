#!/bin/bash

# Loop infinito
while true; do
    sudo supervisorctl reread
    sudo supervisorctl update

    # Esperar 10 segundos
    sleep 10
done