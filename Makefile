.PHONY: deps deps-test deps-codestyle deps-static-analysis test test-lowest test-stable static-analysis codestyle-fix codestyle help
.DEFAULT_GOAL:=help

deps: ## Install project dependencies
	XDEBUG_MODE=off composer update --prefer-dist --no-plugins --no-scripts $(COMPOSER_ARGS)

deps-test: ## Install PHPUnit dependencies
	XDEBUG_MODE=off composer update --prefer-dist --no-plugins --no-scripts $(COMPOSER_ARGS) --working-dir=tools/phpunit

deps-codestyle: ## Install PHP-CS-Fixer dependencies
	XDEBUG_MODE=off composer update --prefer-dist --no-plugins --no-scripts $(COMPOSER_ARGS) --working-dir=tools/php-cs-fixer

deps-static-analysis: ## Install dependencies for psalm
	XDEBUG_MODE=off composer update --prefer-dist --no-plugins --no-scripts $(COMPOSER_ARGS) --working-dir=tools/psalm

test: deps deps-test ## Run the test with locked dependencies
	XDEBUG_MODE=coverage tools/phpunit/vendor/bin/phpunit --colors=always --coverage-text --testdox

test-lowest: test ## Run the tests with lowest dependencies
test-lowest: COMPOSER_ARGS=--prefer-lowest

test-stable: test ## Run the tests with stable dependencies
test-stable: COMPOSER_ARGS=--prefer-stable

static-analysis: deps deps-static-analysis ## Run static code analysis
	XDEBUG_MODE=off tools/psalm/vendor/bin/psalm --report=psalm-report.sarif

codestyle-fix: deps-codestyle ## Fix Codestyle issues
	PHP_CS_FIXER_IGNORE_ENV=1 XDEBUG_MODE=off tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --diff --show-progress=dots --ansi --verbose $(CS_FIXER_ARGS)

codestyle: codestyle-fix ## Show Codestyle issues
codestyle: CS_FIXER_ARGS=--dry-run

help: ## Display this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
