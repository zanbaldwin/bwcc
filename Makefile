P := "$$(tput setaf 2)"
S := "$$(tput setaf 4)"
L := "$$(tput setaf 6)"
R := "$$(tput sgr0)"
usage:
	@echo ""
	@echo " $(L)┏━━━━━━━━━━━━━━━━━━━━━━┓$(R)"
	@echo " $(L)┃   $(R)Xero Import Tool$(L)   ┃$(R)"
	@echo " $(L)┡━━━━━━━━━━━━━━━━━━━━━━┩$(R)"
	@echo " $(L)│ $(R)Available Commands:$(L)  │$(R)"
	@echo " $(L)╰─┬────────────────────╯$(R)"
	@echo "   $(L)├─$(R) $(P)install$(R)           Install third-party packages (Composer dependecies)."
	@echo "   $(L)╰─$(R) $(P)test$(R)              Run all of the following tests:"
	@echo "      $(L)╰─$(R) $(S)cs$(R)             • Run check PHP code for linting and syntax errors."
	@echo ""

MKFILE := $(abspath $(lastword $(MAKEFILE_LIST)))
MKDIR  := $(dir $(MKFILE))

# Composer Dependencies
vendor/autoload.php:
	composer install

# Commonly-used PHP Scripts
vendor/bin/phpcs: vendor/autoload.php

# Shortcuts
install: vendor/autoload.php
test: cs
# Specific Types of Tests
cs: vendor/bin/phpcs
	vendor/bin/phpcs --standard="tests/phpcs.xml" src

.PHONY: usage install test cs

