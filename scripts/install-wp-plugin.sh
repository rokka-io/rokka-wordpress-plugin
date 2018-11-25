#! /bin/bash
# Let's begin...
echo ".........................................."
echo
echo "Preparing to install WordPress plugin"
echo
echo ".........................................."
echo

echo "Changing to plugin path"
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR
cd ..
pwd

echo "Installing composer packages"
curl -s https://getcomposer.org/installer | php
php composer.phar install --no-dev

echo "Installing node modules"

yarn install
echo "Building assets"
yarn build

#echo "Compile translation files"
#for file in `find "languages" -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done


echo "*** Installation of Rokka Plugin complete ***"
