.PHONY: deps test codestyle-deps codestyle fix-codestyle help
.DEFAULT_GOAL:=help

export PHP_VERSION ?= 8

mkfile_dir := $(dir $(abspath $(firstword $(MAKEFILE_LIST))))

deps:
	composer install --prefer-dist --no-plugins --no-scripts

codestyle-deps:
	composer install --prefer-dist --working-dir=tools/php-cs-fixer

test: deps	## Run the testsuite
	XDEBUG_MODE=coverage vendor/bin/phpunit \
		--colors=always \
		--coverage-text \
		--testdox

codestyle: codestyle-deps	## Show PHP-Codestxyle issues
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix \
		--dry-run \
		--diff \
		--format=junit \
		--show-progress=dots


fix-codestyle: codestyle-deps	## Show PHP-Codestxyle issues
	tools/php-cs-fixer/vendor/bin/php-cs-fixer fix \
		--diff \
		--format=junit \
		--show-progress=dots

help:	## Display this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
