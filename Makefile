.PHONY: coverage

coverage:
ifdef GITHUB_ACTION
	phpdbg -qrr vendor/bin/phpunit --exclude-group integration tests --coverage-clover=coverage.xml
else
	phpdbg -qrr vendor/bin/phpunit --exclude-group integration tests --coverage-html=coverage.html
#	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage coverage.html --coverage-src src tests
endif