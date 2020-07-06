#!/usr/bin/env bash

cd "$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

host=648846177135.dkr.ecr.us-east-1.amazonaws.com
repo=eci
name=magento-m2-extension
tag=2.3.5

image=$host/$repo/$name:$tag

docker build --build-arg CONTINUOUS_INTEGRATION="true" -t $image .
