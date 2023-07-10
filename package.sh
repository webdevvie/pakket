#!/usr/bin/env bash
cd $(dirname $0)
rm composer.lock
composer install --no-dev --prefer-dist >/dev/null 2>&1
composer dump >/dev/null 2>&1
./console build . pakket.phar
cd $(dirname $0)
chmod +x pakket.phar
composer install >/dev/null 2>&1