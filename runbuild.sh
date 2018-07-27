#!/usr/bin/env bash
echo "PHPCS"
vendor/bin/phpcs --standard=phpcs.xml .
echo "PHPUNIT"
vendor/bin/phpunit -c . Tests/ --debug
