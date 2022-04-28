#
# 08/03/05 @(#)Makefile	1.3 
#
# Makefile for the data loading section of the LIMS system
#
# The location of the master library
SOURCECODE=/home/chrisr/Webroot/images
#
#
# the location of the Development site
DEVSITE=/net/v20z1/export/home/oracle10g/OraHome_2/Apache/Apache/htdocs/data/images


FILES= mcerts.gif   mcertsSmall.gif upload.jpg upload.gif
#
#
#indexSlow.php

DEVDOCS=$(FILES:%=$(DEVSITE)/%)

devsite: $(FILES)	$(DEVDOCS)


$(DEVDOCS): $$(@F)
	$(RM) $@
	cp $(@F) $@




