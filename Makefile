PEAR=pear
PHPDOC=phpdoc
DOXYGEN=doxygen
ASCIIDOC=asciidoc
XSLTPROC=xsltproc
A2X=a2x
CP=cp
MKDIR=mkdir
RM=rm

all : apidoc webdoc
apidoc : doxygen phpdocumentor
webdoc : userguide docbook

userguide : webdoc/userguide.pdf
docbook : webdoc/docbook/index.html

webdoc/docbook/index.html: README.xml
	$(XSLTPROC) --nonet  \
		--stringparam base.dir "./webdoc/docbook/" \
		docbook.xsl $?

README.xml: README
	$(ASCIIDOC) --unsafe -b docbook -d book -o $@ $?

webdoc/userguide.pdf: README.pdf
	$(CP) $? $@

README.pdf: README
	$(A2X) --asciidoc-opts="--unsafe" --format=pdf --doctype=book --icons README

doxygen:
	$(DOXYGEN) apidoc.doxygen

phpdocumentor:
	$(PHPDOC) -c apidoc.ini

release: AIT-`./extract-version.sh`.tgz

AIT-`./extract-version.sh`.tgz: package.xml
	$(PEAR) package package.xml
	git tag -a -m "Version `./extract-version.sh`"  v`./extract-version.sh`
