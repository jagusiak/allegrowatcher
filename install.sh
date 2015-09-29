#!/bin/bash
echo 'Downloading source'
wget https://github.com/jagusiak/allegrowatcher/archive/master.zip >/dev/null 2>&1
echo 'Unzipping'
unzip master.zip >/dev/null 2>&1
rm master.zip >/dev/null 2>&1
mv allegrowatcher-master allegrowatcher >/dev/null 2>&1
cd allegrowatcher/ >/dev/null 2>&1
echo 'Running composer'
php -r "readfile('https://getcomposer.org/installer');" | php >/dev/null 2>&1
php composer.phar install >/dev/null 2>&1
rm composer.phar >/dev/null 2>&1
cd .. >/dev/null 2>&1
echo 'Done'