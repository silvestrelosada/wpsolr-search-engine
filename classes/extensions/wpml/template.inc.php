<?php

/**
 * Included template file for all multi language plugins.
 */
global $license_manager;
?>


<div id="extension-options" class="wdm-vertical-tabs-content">
	<form action="options.php" method="POST" id='extension_settings_form'>
		<?php
		settings_fields( $settings_fields_name );
		?>

		<div class='wrapper'>
			<h4 class='head_div'><?php echo $plugin_name; ?> plugin Options</h4>

			<div class="wdm_note">

				In this section, you will configure how to manage your multi-language Solr search
				with <?php echo $plugin_name; ?> plugin.
				<br/>

				<?php if ( ! $is_plugin_active ): ?>
					<p>
						Status: <a href="<?php echo $plugin_link; ?>"
						           target="_blank"><?php echo $plugin_name; ?>
							plugin</a> is not activated. First, you need to install and
						activate it to configure WPSOLR.
					</p>
					<p>
						You will also need to re-index all your data if you activated
						<a href="<?php echo $plugin_link; ?>"
						   target="_blank"><?php echo $plugin_name; ?>
							plugin</a>
						after you activated WPSOLR.
					</p>
				<?php else: ?>
					<p>
						Status: <a href="<?php echo $plugin_link; ?>"
						           target="_blank"><?php echo $plugin_name; ?>
							plugin</a>
						is activated. You can now configure WPSOLR to use it.
					</p>
				<?php endif; ?>
			</div>
			<div class="wdm_row">
				<div class='col_left'>Use the <a
						href="<?php echo $plugin_link; ?>"
						target="_blank"><?php echo $plugin_name; ?>
						plugin <?php echo $plugin_version; ?></a>
					to manage multi-language Solr search.
					<br/><br/>Think of re-indexing all your data if <a
						href="<?php echo $plugin_link; ?>"
						target="_blank"><?php echo $plugin_name; ?>
						plugin</a> was installed after WPSOLR.
				</div>
				<div class='col_right'>
					<input <?php echo $is_plugin_active ? '' : 'disabled' ?>
						type='checkbox'
						name='<?php echo $extension_options_name; ?>[is_extension_active]'
						value='is_extension_active'
						<?php checked( 'is_extension_active', isset( $options['is_extension_active'] ) ? $options['is_extension_active'] : '' ); ?>>
				</div>
				<div class="clear"></div>
			</div>


			<?php if ( $is_plugin_active ) { ?>

				<h4 class='head_div'>Select which Solr index will index which language</h4>

				<div class="wdm_note">
					Each language must be stored, and queried, on it's own Solr index.<br/>
					- Language awareness: each language can be configured with it's own schema.xml: language
					specific filters,
					analysers, stemmers .... <br/>
					- Easy to understand: each schema.xml has the same stucture, except for the language specific
					settings
					<br/>
					- Fully featured: autocompletion and suggestions work out of the box by language <br/>
					- Custom: each language can have a totally custom schema.xml if necessary <br/>

				</div>
				<?php
				$option_indexes = new OptionIndexes();
				$solr_indexes   = $option_indexes->get_indexes();

				$languages = $ml_plugin->get_languages();

				foreach ( $solr_indexes as $solr_index_indice => $solr_index ) {
					?>
					<div class="wdm_row">
						<div class='col_left'>
							Solr index '<?php echo $solr_index['index_name'] ?>'
						</div>
						<div class='col_right'>

							Is indexing: &nbsp;
							<select
								name='<?php echo $extension_options_name; ?>[solr_index_indice][<?php echo $solr_index_indice ?>][indexing_language_code]'>

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
										selected( $language_code, isset( $options['solr_index_indice'][ $solr_index_indice ]['indexing_language_code'] )
											? $options['solr_index_indice'][ $solr_index_indice ]['indexing_language_code']
											: '' ),
										$language_code );

								}
								?>

							</select>

							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

							Used as search for this language: &nbsp;
							<input type='checkbox'
							       name='<?php echo $extension_options_name; ?>[solr_index_indice][<?php echo $solr_index_indice ?>][is_default_search]'
							       value='1'
								<?php
								checked( 1, isset( $options['solr_index_indice'][ $solr_index_indice ]['is_default_search'] )
									? $options['solr_index_indice'][ $solr_index_indice ]['is_default_search']
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
				$each_language_has_a_unique_solr_index = $ml_plugin->each_language_has_a_one_solr_index_search();

				echo $each_language_has_a_unique_solr_index
					? ''
					: sprintf( "<div class='solr_error'>Error: <br/>2 Solr indexes are search of the same language. Or a Solr index with no language is a search.</div>" );

				?>

			<?php } ?>

			<div class='wdm_row'>
				<div class="submit">
					<?php if ( ! $license_manager->is_installed || $license_manager->get_license_is_activated( $package_name ) ) { ?>
						<div class="wpsolr_premium_block_class">
							<?php echo $license_manager->show_premium_link( $package_name, OptionLicenses::TEXT_LICENSE_ACTIVATED, true ); ?>
						</div>
						<input <?php echo $is_plugin_active ? '' : 'disabled' ?>
							name="save_selected_options_res_form"
							id="save_selected_extension_groups_form" type="submit"
							class="button-primary wdm-save"
							value="<?php echo $is_plugin_active ? 'Save Options' : sprintf( 'Install and activate the plugin %s first.', $plugin_name ); ?>"/>
					<?php } else { ?>
						<?php echo $license_manager->show_premium_link( $package_name, 'Save Options', true ); ?>
						<br/>
					<?php } ?>
				</div>
			</div>

		</div>


	</form>
</div>

