#!/bin/bash

# change dir to ansible directory
cd $(dirname $0)

php UpVersion.php $1

read TAG < currentversion
sed s/@package_version@/${TAG}/g bin/prodconsole >bin/pharconsole
sed s/@package_version@/${TAG}/g public/indexprod.php >public/indexphar.php

git commit -am "New tag ${TAG}"
#
# Tag & build master branch
#
git tag ${TAG}
git push origin
git push origin --tags