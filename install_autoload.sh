#!/usr/bin/env bash

if [ ! -f ../../app/code/community/Varien/Autoload.php ] && [ ! -f ../../app/code/local/Varien/Autoload.php ]; then
    mkdir -p ../../app/code/local/Varien
    cp Autoload/Autoload.php ../../app/code/local/Varien/
fi

