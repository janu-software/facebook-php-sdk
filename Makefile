.PHONY: coverage

coverage:
ifdef GITHUB_ACTION
	vendor/bin/phpunit --exclude-group integration tests --coverage-clover=coverage.xml
else
	vendor/bin/phpunit --exclude-group integration tests --coverage-html=tests/coverage
endif