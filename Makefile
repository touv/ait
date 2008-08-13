PEAR=pear
PHPDOC=phpdoc
DOXYGEN=doxygen
ASCIIDOC=asciidoc

all: doc

doc: webdoc/README.html

webdoc/README.html: README
	$(ASCIIDOC) -a icons -o $@ $?

apidoc: doxygen phpdocumentor

doxygen:
	$(DOXYGEN) apidoc.doxygen

phpdocumentor:
	$(PHPDOC) -c apidoc.ini
