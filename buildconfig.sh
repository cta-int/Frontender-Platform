#!/bin/bash

set -xeu
echo "{\"imagename\": \"${IMAGE_REPO_NAME}\", \"imagetag\": \"${IMAGE_TAG}\"}" > imageconfig.json
cp stack_config.json UAT-config.json
cp stack_config.json PROD-config.json
sed -i "s/UAT-/PROD-/g" PROD-config.json
sed -i "s/uat-/prod-/g" PROD-config.json
