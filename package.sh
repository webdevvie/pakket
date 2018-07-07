#!/usr/bin/env bash
cd $(dirname $0)
composer.phar install --no-dev --prefer-dist >/dev/null 2>&1
composer.phar dump >/dev/null 2>&1
./console build . pakket.phar
cd $(dirname $0)
chmod +x pakket.phar
composer.phar install >/dev/null 2>&1