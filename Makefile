PEAR=pear
PHPDOC=phpdoc
DOXYGEN=doxygen
ASCIIDOC=asciidoc
XSLTPROC=xsltproc
A2X=a2x
CP=cp
MKDIR=mkdir
RM=rm
VERSION=`./extract-version.sh`
CURVER=AIT-$(VERSION).tgz
APIKEY=5cd8785b-c05c-72d4-71f5-fa6fc9c39839
PEARHOST=http://pear.respear.net/respear/


all : apidoc webdoc
apidoc : doxygen phpdocumentor
webdoc : userguide docbook

userguide : webdoc/userguide.pdf
docbook : webdoc/docbook/index.html 

webdoc/docbook/index.html: userguide.xml docbook.xsl
	$(XSLTPROC) --nonet  \
		--stringparam base.dir "./webdoc/docbook/" \
		docbook.xsl userguide.xml

userguide.xml: userguide.txt
	$(ASCIIDOC) --unsafe -b docbook -d book -o $@ $?

webdoc/userguide.pdf: userguide.pdf
	$(CP) $? $@

userguide.pdf: userguide.txt
	$(A2X) --asciidoc-opts="--unsafe" --format=pdf --doctype=book --icons  $?

doxygen:
	$(DOXYGEN) apidoc.doxygen

phpdocumentor:
	$(PHPDOC) -c apidoc.ini
release: tagging pearing

tagging: $(CURVER)
	git tag -a -m "Version $(VERSION)"  v$(VERSION)

pearing: $(CURVER)
	@read -p "Who are you ? " toto && cat $(CURVER) | curl -u `echo $$toto`:$(APIKEY) -X POST --data-binary @- $(PEARHOST)

$(CURVER): package.xml
	$(PEAR) package $?
