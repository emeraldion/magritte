#!/usr/bin/env bash
#                                _ __  __
#    ____ ___  ____ _____ ______(_) /_/ /____
#   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
#  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
# /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
#                 /____/
#
# (c) Claudio Procida 2026
#
# @format
#

# Import common utils
source $(dirname "$0")/common.sh

SRC="node_modules/@emeraldion/aoi/css/aoi.css \
node_modules/@emeraldion/aoi/dist/aoi.min.css"
DEST=assets/styles

logo
echo "Copying @emeraldion/aoi dist files..."

for src in $SRC; do
    cp $src $DEST
done
