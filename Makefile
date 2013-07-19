THEMES=dark light

default: themes static/cache/clientdata.json

themes: $(addprefix static/, $(addsuffix .css, $(THEMES)))

static/%.css: src/sass/themes/%.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

static/cache/clientdata.json:
	./bin/make_static_client_data

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
	find ./cache -maxdepth 1 -type f -not \( -name "API_*" -or -name "sess_*" \) -delete
	find ./static/cache -maxdepth 1 -type f -not -name ".empty_file" -delete
	make

clear-api-cache:
	find ./cache -name "API_*" -delete

clear-sessions:
	find ./cache -name "sess_*" -delete

.PHONY: default tags tests db-tests all-tests test-coverage clear-harmless-cache clear-api-cache clear-sessions themes staticcache
