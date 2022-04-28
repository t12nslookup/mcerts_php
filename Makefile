#
# 08/05/05 @(#)Makefile	1.12 
#
# Makefile for the data loading section of the LIMS system
#
# The location of the master library
SOURCECODE=/home/chrisr/Webroot
#
#
# the location of the Development site
DEVSITE=/net/v20z1/export/home/oracle10g/OraHome_2/Apache/Apache/htdocs/data


SUBDIRS= uploads utility reports Connections css images mcerts certificates
#
# This is a library of software which should be copied once whenever it is updated this Makefile will not account for this
EXTERNALCODE=adodb

FILES=upload.php show.php index.php index.html test.php parseCirosICP.php test.rbx foo.rhtml test.rhtml record.rhtml AtomScannerParser.rbx Result.rbx
#
#
#indexSlow.php

DEVDOCS=$(FILES:%=$(DEVSITE)/%)
system: $(FILES) $(DEVDOCS) php

directories:
	-@if [ ! -h SCCS ] ; then \
			echo "<<<Linking SCCS :   >>>"; \
			ln -s $(SOURCECODE)/SCCS SCCS ; \
			fi ; 
	-@for i in ${SUBDIRS}; do \
	(       \
		if [ ! -d $$i ] ; then \
			echo "<<<Creating Directory:  $$i >>>"; \
			mkdir $$i;\
			fi ; \
			cd $$i; \
			if [ ! -h SCCS ] ; then \
			echo "<<<Linking SCCS :  $$i >>>"; \
			ln -s $(SOURCECODE)/$$i/SCCS SCCS ; \
			fi ; \
			cd .. ; \
		); done



sccsinfo:
	-@for i in ${SUBDIRS}; do \
		(       echo "<<<sccs info:$$i>>>"; \
			cd $$i; \
			sccs info ;\
			cd ..; \
		); done

$(DEVDOCS): $$(@F)
	$(RM) $@
	cp $(@F) $@
	chmod 555 $@



devsite: $(FILES)	$(DEVDOCS)
	 -@for i in ${SUBDIRS}; do \
 	(       echo "<<<sccs info:$$i>>>"; \
		cd $$i; \
		make devsite ;\
		cd ..; \
	); done


php: /home/chrisr/setperms
	-@/home/chrisr/setperms; \
	for i in ${SUBDIRS}; do \
		(	echo "<<<BUILDING PHP:  $$i >>>"; \
			cd $$i; \
			${MAKE} ; \
			cd ..; \
		); done

