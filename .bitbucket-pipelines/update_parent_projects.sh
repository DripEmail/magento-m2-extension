#!/bin/bash

# Setup SSH connection to uat-dripm2.robofirm.net
#(mkdir -p ~/.ssh &&
#(umask  077 ; echo $UAT_SSH_KEY | base64 --decode > ~/.ssh/id_rsa) && 
#(umask  077 ; echo $UAT_SSH_KNOWN_HOST | base64 --decode >> ~/.ssh/known_hosts) &&

# Update drip/connect module and push back to parent projects
#ssh -q webuser@uat-dripm2.robofirm.net "
#  cd /tmp &&
#  ([[ -d translations-magento-2-1 ]] || git clone git@bitbucket.org:robofirm/translations-magento-2-1.git --depth 1) &&
#  cd translations-magento-2-1 &&
#  composer update drip/module-connect &&
#  (git diff composer.lock && git add composer.lock && git commit -a -m \"Updated drip/module-connect to latest master\" && git push || true) && 
#  cd .. &&
#  ([[ -d translations-magento-2-0 ]] || git clone git@bitbucket.org:robofirm/translations-magento-2-0.git --depth 1) &&
#  cd translations-magento-2-0 &&
#  composer update drip/module-connect &&
#  (git diff composer.lock && git add composer.lock && git commit -a -m \"Updated drip/module-connect to latest master\" && git push || true)
#") || exit 1
