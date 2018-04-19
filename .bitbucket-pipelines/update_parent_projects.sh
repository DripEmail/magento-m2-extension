#!/bin/bash

# Setup SSH connection to uat-dripm2.robofirm.net
#(mkdir -p ~/.ssh &&
#(umask  077 ; echo $UAT_SSH_KEY | base64 --decode > ~/.ssh/id_rsa) && 
#(umask  077 ; echo $UAT_SSH_KNOWN_HOST | base64 --decode >> ~/.ssh/known_hosts) &&

# Update drip/connect module and push back to parent projects
#ssh -q webuser@uat-dripm2.robofirm.net "
#  cd /tmp &&
#  ([[ -d dripm2 ]] || git clone git@bitbucket.org:robofirm/drip-magento2.git --depth 1) &&
#  cd dripm2 &&
#  composer update drip/connect &&
#  (git diff composer.lock && git add composer.lock && git commit -a -m \"Updated drip/connect to latest master\" && git push || true) 
#") || exit 1
