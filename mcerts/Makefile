
#
# 08/03/05 @(#)Makefile	1.2 
#
# Makefile for the data loading section of the LIMS system
#
# The location of the master library
SOURCECODE=/home/chrisr/Webroot/mcerts
#
#
# the location of the Development site
DEVSITE=/net/v20z1/export/home/oracle10g/OraHome_2/Apache/Apache/htdocs/data/mcerts


FILES= CalculateLevelSummary.php HighLevelSummary.php QC.php \
	ResultsTrace.php addManualValidationSet.php \
	addUpdateValidationSet.php autoAddBatches.php \
	compoundsInSample.php datefunctions.php deleteBatch.php \
	deleteCompound.php deleteReferenceMaterial.php editValues.php \
	functionQC.php mcertsindex.php selectBatches.php updateCompound.php \
	updateReferenceMaterial.php newindex.php viewReferenceMaterials.php \
	addReferenceMaterial.php menu.php addNewCompound.php fix.php locked.php \
	CalculateAlternateSummary.php McertsLevelSummary.php QC2.php viewBatches.php PassFailNumber.php \
	unlockAll.php addManualAnalyte.php addBatchesToAnalyte.php deleteDocument.php uploadDocument.php \
	createBatchesForManualDataEntry.php scriptaculous.js effects.js prototype.js
#
#indexSlow.php

DEVDOCS=$(FILES:%=$(DEVSITE)/%)

devsite: $(FILES)	$(DEVDOCS)



$(DEVDOCS): $$(@F)
	$(RM) $@
	cp $(@F) $@
	chmod 555 $@




