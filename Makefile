PEAR=pear
PHPDOC=phpdoc
DOXYGEN=doxygen
ASCIIDOC=asciidoc
A2X=a2x
CP=cp

all: doc
apidoc: doxygen phpdocumentor
webdoc: webdoc/userguide.pdf 

doc: webdoc/README.html

webdoc/README.html: README
	$(ASCIIDOC) -a icons -o $@ $?

webdoc/userguide.pdf: README.pdf
	$(CP) $? $@

README.pdf: README
	$(A2X) --format=pdf --doctype=book --icons README

doxygen:
	$(DOXYGEN) apidoc.doxygen

phpdocumentor:
	$(PHPDOC) -c apidoc.ini
