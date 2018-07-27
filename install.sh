#!/bin/bash
cd $(dirname $0)
./package.sh
sudo cp pakket.phar /usr/bin/
