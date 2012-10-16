THEMES=dark

default: themes

themes: $(addprefix static/, $(addsuffix .css, $(THEMES)))

static/%.css: src/sass/themes/%.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

tests:
	@make -s clear-harmless-cache
	phpunit --exclude-group expensive,database

db-tests:
	@make -s clear-harmless-cache
	phpunit --group database

all-tests:
	@make -s clear-harmless-cache
	phpunit

test-coverage:
	@make -s clear-harmless-cache
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

.PHONY: default tags tests db-tests all-tests test-coverage clear-harmless-cache clear-api-cache clear-sessions themes

