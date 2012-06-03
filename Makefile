static/chrome.css: src/sass/root.scss src/sass/*.scss
	sass --unix-newlines -t compact $< | tr -s '\n' > $@

tags:
	ctags -e -R .

.PHONY: tags
