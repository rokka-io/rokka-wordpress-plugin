#!/bin/bash

HERE=`dirname $0`
ROOT="$HERE/../.."
DOCKER_ROOT="/var/www/html/wp-content/plugins/rokka-integration"

npm run wp-env run cli "wp i18n make-pot --exclude=\"tests,release\" $DOCKER_ROOT $DOCKER_ROOT/languages/rokka-integration.pot"
