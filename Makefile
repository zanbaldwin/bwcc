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
	@echo "   $(L)├─$(R) $(P)build$(R)             Pull and build Docker containers."
	@echo "   $(L)├─$(R) $(P)install$(R)           Install third-party packages (Composer dependecies)."
	@echo "   $(L)├─$(R) $(P)run$(R)               Start the Docker containers to run the web application."
	@echo "   $(L)╰─$(R) $(P)test$(R)              Run all of the following tests:"
	@echo "      $(L)╰─$(R) $(S)cs$(R)             • Run check PHP code for linting and syntax errors."
	@echo ""
	@echo "   $(L)╭$(R)                                                    $(L)╮$(R)"
	@echo "   $(L)│$(R) Performs basic shortcuts; use $(P)bin/docker$(R) and other $(L)│$(R)"
	@echo "   $(L)│$(R) approprate commands for advanced usage.            $(L)│$(R)"
	@echo "   $(L)╰$(R)                                                    $(L)╯$(R)"
	@echo ""

MKFILE := $(abspath $(lastword $(MAKEFILE_LIST)))
MKDIR  := $(dir $(MKFILE))

# Composer Dependencies
vendor/autoload.php:
	if [ "$$(which composer)" = "" ] && [ ! -f "bin/composer.phar" ]; then \
	    "$(MKDIR)/bin/docker" bin/install-composer; \
	fi
	"$(MKDIR)/bin/docker" composer install

# Commonly-used PHP Scripts
vendor/bin/phpcs: vendor/autoload.php

# Shortcuts
build:
	docker-compose build --pull --force
install: vendor/autoload.php
run: install
	docker-compose up -d
test: cs
# Specific Types of Tests
cs: vendor/bin/phpcs
	"$(MKDIR)/bin/docker" vendor/bin/phpcs --standard="tests/phpcs.xml" src

.PHONY: usage build install test cs

