THEMES=dark light

default: themes static/cache/clientdata.json cache/OsmiumCache_top_kills static/cache/sitemap-root.xml.gz

themes: $(addprefix static/, $(addsuffix .css, $(THEMES))) static/fatal.css

static/fatal.css: src/sass/fatal.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

static/%.css: src/sass/themes/%.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

static/cache/clientdata.json:
	./bin/make_static_client_data

static/cache/sitemap-root.xml.gz:
	./bin/make_sitemap

cache/OsmiumCache_top_kills:
	./bin/cache_top_kills

tests:
	@$(MAKE) -s clear-harmless-cache
	phpunit --exclude-group expensive,database

db-tests:
	@$(MAKE) -s clear-harmless-cache
	phpunit --group database

all-tests:
	@$(MAKE) -s clear-harmless-cache
	phpunit

test-coverage:
	@$(MAKE) -s clear-harmless-cache
	phpunit --coverage-html tests/coverage

tags:
	ctags -e -R .

clear-harmless-cache:
	find ./cache -maxdepth 1 -type f -not \( -name ".empty_file" -or -name "API_*" -or -name "sess_*" \) -delete
	find ./static/cache -maxdepth 1 -type f -not -name ".empty_file" -delete
	$(MAKE)

clear-api-cache:
	find ./cache -name "API_*" -delete

clear-sessions:
	find ./cache -name "sess_*" -delete

post-eve-schema-update: reindex-loadouts

update-charinfo:
	./bin/parallelize 16 ./bin/update_charinfo

reindex-loadouts:
	./bin/truncate_loadout_index
	./bin/parallelize 8 ./bin/reindex_loadouts

reformat-deltas:
	./bin/truncate osmium.fittingdeltas
	./bin/parallelize 8 ./bin/reformat_deltas

reformat-editable-formatted-contents:
	./bin/parallelize 8 ./bin/reformat_editable_formatted_contents

.PHONY: default tags tests db-tests all-tests test-coverage		\
 clear-harmless-cache clear-api-cache clear-sessions themes		\
 staticcache post-eve-schema-update reindex-loadouts reformat-deltas	\
 reformat-editable-formatted-contents
