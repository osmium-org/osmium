MD_SOURCES = $(shell find md -maxdepth 1 -type f -name "*.md")
XHTML_TARGETS = $(patsubst md/%.md, %.xhtml, $(MD_SOURCES))

default: t.css $(XHTML_TARGETS)

%.css: %.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

%.xhtml: md/%.md
	./make_page $< $@

clean:
	rm -f *.css *.xhtml
