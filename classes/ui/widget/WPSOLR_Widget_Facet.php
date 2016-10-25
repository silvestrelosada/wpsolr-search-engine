<?php

/**
 * WPSOLR Widget Facets.
 */
class WPSOLR_Widget_Facet extends WPSOLR_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'wpsolr_widget_facets', // Base ID
			__( 'WPSOLR Facets', 'wpsolr_admin' ), // Name
			array( 'description' => __( 'Display Solr Facets', 'wpsolr_admin' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {

		if ( WPSOLR_Global::getOption()->get_search_is_replace_default_wp_search() && WPSOLR_Global::getOption()->get_search_is_use_current_theme_search_template() && WPSOLR_Query_Parameters::is_wp_search() ) {

			echo $args['before_widget'];

			$results = WPSOLR_Global::getSolrClient()->display_results( WPSOLR_Global::getQuery() );

			echo '<div id="res_facets">' . WPSOLR_UI_Facets::Build(
					WPSOLR_Data_Facets::get_data(
						WPSOLR_Global::getQuery()->get_filter_query_fields_group_by_name(),
						WPSOLR_Global::getOption()->get_facets_to_display(),
						$results[1] ),
					OptionLocalization::get_options() ) . '</div>';

			echo $args['after_widget'];
		}

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	/*
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'wpsolr_admin' );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
			       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}*/
	public function form( $instance ) {
		?>
		<p>
			Position this widget where you want your facets to appear.
		</p>
		<p>
			Facets are dynamic filters users can click on to filter search results, like categories, or tags. Facets
			must have been defined in WPSOLR admin pages.
		</p>
		<p>
			In next releases of WPSOLR, you will be able to configure your widget layout, to match your theme layout.
		</p>

		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	/*
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}*/

}