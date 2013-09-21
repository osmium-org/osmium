THEMES=dark light

default: themes static/cache/clientdata.json cache/OsmiumCache_top_kills

themes: $(addprefix static/, $(addsuffix .css, $(THEMES)))

static/%.css: src/sass/themes/%.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

static/cache/clientdata.json:
	./bin/make_static_client_data

cache/OsmiumCache_top_kills:
	./bin/cache_top_kills

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
	find ./cache -maxdepth 1 -type f -not \( -name ".empty_file" -or -name "API_*" -or -name "sess_*" \) -delete
	find ./static/cache -maxdepth 1 -type f -not -name ".empty_file" -delete
	make

clear-api-cache:
	find ./cache -name "API_*" -delete

clear-sessions:
	find ./cache -name "sess_*" -delete

post-eve-schema-update:
	echo {0..7} | xargs -n 1 -P 8 ./bin/update_loadout_dogma_attribs 8

update-charinfo:
	echo {0..15} | xargs -n 1 -P 16 ./bin/update_charinfo 16

.PHONY: default tags tests db-tests all-tests test-coverage clear-harmless-cache clear-api-cache clear-sessions themes staticcache post-eve-schema-update
