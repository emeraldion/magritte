#!/bin/zsh
#                                _ __  __
#    ____ ___  ____ _____ ______(_) /_/ /____
#   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
#  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
# /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
#                 /____/
#
# (c) Claudio Procida 2026
#

# Import common utils
source $(dirname "$0")/common.sh
# Display project logo
logo
# Setup node
nvm use