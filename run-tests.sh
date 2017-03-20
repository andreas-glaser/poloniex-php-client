#!/usr/bin/env bash

cd "$(dirname "$(readlink -f "$0")")"

[ ! -d "./bin" ] || [ ! -d "./vendor" ]; {
  composer install
}

./bin/phpunit -c phpunit.xml --stop-on-error --stop-on-failure $@