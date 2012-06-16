default: static/chrome.css

static/chrome.css: src/sass/root.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

tests:
	phpunit --exclude-group expensive,database

db-tests:
	phpunit --group database

all-tests:
	phpunit

test-coverage:
	phpunit --coverage-html tests/coverage

tags:
	ctags -e -R .

.PHONY: default tags tests
