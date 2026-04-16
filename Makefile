.DEFAULT_GOAL := help
.PHONY: help test test-docker analyse coverage bench clean

help: ## Show available targets
	@awk 'BEGIN{FS=":.*## "}/^[a-zA-Z_-]+:.*## /{printf "  %-14s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

test: ## Run PHPUnit suite locally (fast)
	vendor/bin/phpunit

test-docker: ## Run tests via docker with R parity fallback
	composer run test

analyse: ## Run PHPStan at level 5
	vendor/bin/phpstan analyse --memory-limit=512M

coverage: ## Run tests with clover coverage report (requires pcov)
	vendor/bin/phpunit --coverage-clover build/coverage.xml --coverage-text

bench: ## Run benchmark suite (writes statguard-perf.json)
	php tests/BenchmarkStatGuard.php json

clean: ## Remove generated build artefacts
	rm -rf build/ .phpunit.cache .phpunit.result.cache statguard-perf.json statguard-coverage.json
