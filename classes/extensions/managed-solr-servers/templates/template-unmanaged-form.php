<?php
/**
 * Unmanaged Solr server form
 */
?>

<div id="solr-configuration-tab" class="wdm-vertical-tabs-content">
	<div class='wrapper'>
		<h4 class='head_div'>Solr Configuration</h4>

		<div class="wdm_note">

			WPSOLR is compatible with the Solr versions listed at the following page: <a
				href="http://www.wpsolr.com/releases#1.0" target="__wpsolr">Compatible Solr versions</a>.

			Your first action must be to download the two configuration files (schema.xml,
			solrconfig.xml) listed in the online release section, and upload them to your Solr instance.
			Everything is described online.

		</div>
		<div class="wdm_row">
			<div class="submit">
				<a href='admin.php?page=solr_settings&tab=solr_indexes' class="button-primary wdm-save">I
					uploaded my 2 compatible configuration files to my Solr core >></a>
			</div>
		</div>
	</div>
</div>