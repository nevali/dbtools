#### Docbook 5 Workflow
#### Mo McRoberts <mo@nevali.net>

NAME ?= book
BOOKLANG ?= en

DOCBOOK ?= $(NAME).$(BOOKLANG).xml
FO ?= fo/$(NAME).$(BOOKLANG).fo
PDF ?= pdf/$(NAME).$(BOOKLANG).pdf
PS ?= postscript/$(NAME).$(BOOKLANG).ps
EPUB ?= epub/book.$(BOOKLANG).epub
TOC_NCX ?= epub/toc.$(BOOKLANG).ncx
EPUB_MANIFEST ?= epub/manifest.$(BOOKLANG).xml
HTML ?= html/$(NAME).$(BOOKLANG).html
WEBSITE ?= website
RTF ?= rtf/$(NAME).$(BOOKLANG).rtf

DBTOOLS ?= $(shell cd dbtools && pwd)
PHP ?= php

all: $(WEBSITE) $(HTML) $(RTF)

fo: $(FO)
pdf: $(PDF)
ps: $(PS)
epub: $(EPUB)
web: $(WEBSITE)
html: $(HTML)
rtf: $(RTF)

wellformed: $(DOCBOOK)
	xmllint --xinclude --noout --nonet $(DOCBOOK)

valid: $(DOCBOOK)
	xmllint --xinclude --noout --nonet --valid $(DOCBOOK)

define container_xml
<?xml version="1.0" encoding="UTF-8" ?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/book.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
endef

export container_xml

## Generate XSL-FO from DocBook-XML
$(FO): $(DOCBOOK) $(CHAPTER_SRC)
	mkdir -p `dirname $(FO)`
	xsltproc --nonet \
		--xinclude \
		--output $@ \
		--stringparam fo.extensions 1 \
		--stringparam paper.type A4 \
		--stringparam double.sided 1 \
		--stringparam body.font.family "Palatino" \
		--stringparam body.font.master 12 \
		--stringparam title.font.family "Gill Sans" \
		'http://docbook.sourceforge.net/release/xsl/current/fo/docbook.xsl' \
		$(DOCBOOK)

## Generate PDF from XSL-FO
$(PDF): $(FO)
	mkdir -p `dirname $(PDF)`
	xmlroff -o $@ --format=pdf $<

## Generate PostScript from XSL-FO
$(PS): $(FO) 
	mkdir -p `dirname $(PS)`
	xmlroff -o $@ --format=postscript $<

## Generate RTF via DSSSL/OpenJade
## Generate PostScript from XSL-FO
$(RTF): $(DOCBOOK) 
	mkdir -p `dirname $(RTF)`
	xmllint --xinclude --nonet $(DOCBOOK) > single.xml
	$(PHP) -f $(DBTOOLS)/db2rtf.php single.xml $(RTF)


## Generate an HTML site from DocBook-XML
$(WEBSITE): $(DOCBOOK) $(CHAPTER_SRC)
	rm -rf $(WEBSITE)
	mkdir -p $(WEBSITE)
	xsltproc --nonet \
		--xinclude \
		--output $(WEBSITE)/ \
		--stringparam chunker.output.encoding UTF-8 \
		--stringparam chunker.output.omit-xml-declaration yes \
		--stringparam html.stylesheet 'doc.css' \
		--stringparam chunk.section.depth 0 \
		'http://docbook.sourceforge.net/release/xsl/current/html/chunk.xsl' \
		$(DOCBOOK)
	ln -s $(DBTOOLS)/doc.css $(WEBSITE)/doc.css

## Generate a single HTML file from DocBook-XML
$(HTML): $(DOCBOOK) $(CHAPTER_SRC)
	mkdir -p `dirname $(HTML)`
	xsltproc --nonet \
		--xinclude \
		--output $(HTML) \
		--stringparam html.stylesheet 'doc.css' \
		'http://docbook.sourceforge.net/release/xsl/current/html/docbook.xsl' \
		$(DOCBOOK)
	rm -f `dirname $(HTML)`/doc.css
	ln -s $(DBTOOLS)/doc.css `dirname $(HTML)`/doc.css

$(EPUB): $(EPUB_MANIFEST) $(CHAPTERS) $(TOC_NCX)
	rm -rf work $@
	mkdir work work/META-INF work/OEBPS work/OEBPS/styles
	echo 'application/epub+zip' > work/mimetype
	cp $(CHAPTERS) work/OEBPS
	cp $(EPUB_MANIFEST) work/OEBPS/book.opf
	cp $(TOC_NCX) work/OEBPS/
	for i in $(CHAPTER_CSS) ; do \
		if test -r epub/styles/$$i ; then \
			cp epub/styles/$$i work/OEBPS/styles/ ; \
		else \
			cp styles/$$i work/OEBPS/styles/ ; \
		fi ; \
	done
	echo "$$container_xml" > work/META-INF/container.xml
	cd work && zip -r -9 ../$@ .
