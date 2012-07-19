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

clear-harmless-cache:
	rm -f cache/OsmiumCache_* static/cache/*.{js,html}
	rm -Rf cache/CSS cache/HTML cache/URI

clear-api-cache:
	rm -f cache/API_*

clear-sessions:
	rm -f cache/sess_*

.PHONY: default tags tests clear-harmless-cache clear-api-cache clear-sessions

