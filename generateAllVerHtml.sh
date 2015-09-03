#!/bin/bash
TEMP_SRC_CD=$PWD
TEMP_DEST_CD=${0%/*}

if [ -n ${TEMP_DEST_CD} ]; then
    cd ${TEMP_DEST_CD}
fi

./update_html.rb jp
./update_html.rb en

if [ -n ${TEMP_DEST_CD} ]; then
    cd ${TEMP_SRC_CD}
fi
