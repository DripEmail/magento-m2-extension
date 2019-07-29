#!/bin/bash

# Setup SSH connection to composer.rmgmedia.com
(mkdir -p ~/.ssh &&
(umask  077 ; echo $COMPOSER_SSH_KEY | base64 --decode > ~/.ssh/id_rsa) &&
(umask  077 ; echo $COMPOSER_SSH_KNOWN_HOST | base64 --decode >> ~/.ssh/known_hosts) &&

# Run Satis
ssh -q webuser@composer.rmgmedia.com "
 cd /var/www/composer &&
 php vendor/composer/satis/bin/satis build magento2-module.json htdocs/magento2/module transperfect/module-globallink
") || exit 1