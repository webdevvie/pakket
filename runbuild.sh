#!/usr/bin/env bash
echo "PHPCS"
vendor/bin/phpcs --standard=phpcs.xml .
echo "PHPUNIT"
vendor/bin/phpunit --coverage-html coverage -c . Tests/ --debug
