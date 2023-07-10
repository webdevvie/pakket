#!/usr/bin/env bash
cd $(dirname $0)
/usr/local/Cellar/php/8.2.4/bin/php ./console build .
/usr/local/Cellar/php@8.1/8.1.17/bin/php ./console build .