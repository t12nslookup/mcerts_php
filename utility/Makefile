#
# 08/03/05 @(#)Makefile	1.2 
#
# Makefile for the data loading section of the LIMS system
#
# The location of the master library
SOURCECODE=/home/chrisr/Webroot/utility
#
#
# the location of the Development site
DEVSITE=/net/v20z1/export/home/oracle10g/OraHome_2/Apache/Apache/htdocs/data/utility


FILES= mathematics.php stats.php
#
#
#indexSlow.php

DEVDOCS=$(FILES:%=$(DEVSITE)/%)

devsite: $(FILES)	$(DEVDOCS)


$(DEVDOCS): $$(@F)
	$(RM) $@
	cp $(@F) $@




