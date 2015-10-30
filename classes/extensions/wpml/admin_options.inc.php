<?php

/**
 * Included file to display admin options
 */


WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::EXTENSION_WPML, true );
WpSolrExtensions::require_once_wpsolr_extension( WpSolrExtensions::OPTION_INDEXES, true );

$extension_options_name = 'wdm_solr_extension_wpml_data';
$settings_fields_name   = 'solr_extension_wpml_options';

$array_extension_options = get_option( $extension_options_name );
$is_plugin_active        = WpSolrExtensions::is_plugin_active( WpSolrExtensions::EXTENSION_WPML );
?>

<div id="extension_wpml-options" class="wdm-vertical-tabs-content">
	<form action="options.php" method="POST" id='extension_wpml_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		$solr_extension_wpml_options = get_option( $extension_options_name, array(
			'is_extension_active' => '0',
		) );
		?>

		<div class='wrapper'>
			<h4 class='head_div'>WPML plugin Options</h4>

			<div class="wdm_note">

				In this section, you will configure how to manage your multi-language Solr search with WPML plugin.
				<br/>

				<?php if ( ! $is_plugin_active ): ?>
					<p>
						Status: <a href="https://wpml.org/"
						           target="_blank">WPML
							plugin</a> is not activated. First, you need to install and
						activate it to configure WPSOLR.
					</p>
					<p>
						You will also need to re-index all your data if you activated
						<a href="https://wpml.org/"
						   target="_blank">WPML
							plugin</a>
						after you activated WPSOLR.
					</p>
				<?php else: ?>
					<p>
						Status: <a href="https://wpml.org/"
						           target="_blank">WPML
							plugin</a>
						is activated. You can now configure WPSOLR to use it.
					</p>
				<?php endif; ?>
			</div>
			<div class="wdm_row">
				<div class='col_left'>Use the <a
						href="https://wpml.org/"
						target="_blank">WPML
						plugin (WPML Multilingual CMS > 3.1.6)</a>
					to manage multi-language Solr search.
					<br/><br/>Think of re-indexing all your data if <a
						href="https://wpml.org/"
						target="_blank">WPML
						plugin</a> was installed after WPSOLR.
				</div>
				<div class='col_right'>
					<input type='checkbox'
					       name='wdm_solr_extension_wpml_data[is_extension_active]'
					       value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $solr_extension_wpml_options['is_extension_active'] ) ? $solr_extension_wpml_options['is_extension_active'] : '' ); ?>>
				</div>
				<div class="clear"></div>
			</div>


			<h4 class='head_div'>Select which Solr index will index which language</h4>

			<div class="wdm_note">
				Each language must be stored, and queried, on it's own Solr index.<br/>
				- Language awareness: each language can be configured with it's own schema.xml: language
				specific filters,
				analysers, stemmers .... <br/>
				- Easy to understand: each schema.xml has the same stucture, except for the language specific settings
				<br/>
				- Fully featured: autocompletion and suggestions work out of the box by language <br/>
				- Custom: each language can have a totally custom schema.xml if necessary <br/>

			</div>
			<?php
			$option_indexes = new OptionIndexes();
			$solr_indexes   = $option_indexes->get_indexes();

			$pluginWpml = PluginWpml::create();
			$languages  = $pluginWpml->get_languages();

			foreach ( $solr_indexes as $solr_index_indice => $solr_index ) {
				?>
				<div class="wdm_row">
					<div class='col_left'>
						Solr index '<?php echo $solr_index['index_name'] ?>'
					</div>
					<div class='col_right'>

						Is indexing: &nbsp;
						<select
							name='wdm_solr_extension_wpml_data[solr_index_indice][<?php echo $solr_index_indice ?>][indexing_language_code]'>

							<?php
							// Empty option
							echo sprintf( "<option value='%s' %s>%s</option>",
								'',
								'',
								'No language'
							);
							?>

							<?php
							foreach ( $languages as $language_code => $language ) {

								echo sprintf( "<option value='%s' %s>%s</option>",
									$language_code,
									selected( $language_code, isset( $solr_extension_wpml_options['solr_index_indice'][ $solr_index_indice ]['indexing_language_code'] )
										? $solr_extension_wpml_options['solr_index_indice'][ $solr_index_indice ]['indexing_language_code']
										: '' ),
									$language_code );

							}
							?>

						</select>

						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

						Used as search for this language: &nbsp;
						<input type='checkbox'
						       name='wdm_solr_extension_wpml_data[solr_index_indice][<?php echo $solr_index_indice ?>][is_default_search]'
						       value='1'
							<?php
							checked( 1, isset( $solr_extension_wpml_options['solr_index_indice'][ $solr_index_indice ]['is_default_search'] )
								? $solr_extension_wpml_options['solr_index_indice'][ $solr_index_indice ]['is_default_search']
								: '' );
							?>
							>

					</div>
					<div class="clear"></div>
				</div>
				<?php
			} // end of languages loop
			?>

			<?php

			// One Solr index by language ?
			$each_language_has_a_unique_solr_index = $pluginWpml->each_language_has_a_one_solr_index_search();

			echo $each_language_has_a_unique_solr_index
				? ''
				: sprintf( "<div class='solr_error'>Error: <br/>2 Solr indexes are search of the same language. Or a Solr index with no language is a search.</div>" );

			?>

			<div class='wdm_row'>
				<div class="submit">
					<input name="save_selected_options_res_form"
					       id="save_selected_extension_wpml_form" type="submit"
					       class="button-primary wdm-save" value="Save Options"/>
				</div>
			</div>
		</div>

	</form>
</div>