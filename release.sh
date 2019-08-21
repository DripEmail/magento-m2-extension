#!/bin/bash
set -e

MODULE_XML_PATH="etc/module.xml"
COMPOSER_JSON_PATH="composer.json"

# Check for uncommitted changes.
if (! git diff-index --quiet HEAD --); then
  echo "There are uncommitted changes, please commit them. Exiting..."
  exit
fi

# Get expected version.
echo "What version are you releasing?"
read release_version

# Check for XML versions
if (! grep "setup_version=\"$release_version\"" $MODULE_XML_PATH > /dev/null); then
  echo "$MODULE_XML_PATH does not contain the right version. Exiting..."
  exit
fi

if (! grep "\"version\": \"$release_version\"" $COMPOSER_JSON_PATH > /dev/null); then
  echo "$COMPOSER_JSON_PATH does not contain the right version. Exiting..."
  exit
fi

echo "All versions check out. Generating tarball..."

git archive --format=zip HEAD > drip_m2connect-$(echo -n $release_version | tr '.' '_')-$(date "+%Y-%m-%d").zip

echo "Tarball generated. Don't forget to tag a release and push the tag."
