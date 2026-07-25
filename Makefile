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
.PHONY: docs test install update

update:
	composer update
	yarn upgrade
install:
	composer install
	yarn install
create-test-db:
	mysql -u root -p < schemas/magritte.sql

test: install
	vendor/bin/phpunit --test-suffix=Test.php test

# Formatting goals
format:
	yarn format
format-strings:
	vendor/bin/emerails_localize format --recursive
# format-tests:
# 	yarn format-tests
# format-all: format format-strings format-tests
format-all: format format-strings

# Localization goals
check-strings:
	vendor/bin/emerails_localize check --recursive --strict
extract-strings:
	scripts/extract_strings.sh

# Deployment goals
package-vendor:
	scripts/package_vendor.sh

# Security goals
audit:
	composer audit
	yarn audit