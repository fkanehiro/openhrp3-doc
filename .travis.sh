#!/bin/bash

set -x

sudo apt-get update -qq
sudo apt-get install -qq -y ruby

./generateAllVerHtml.sh

