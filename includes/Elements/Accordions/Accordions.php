<?php

namespace ZionBuilder\Elements;

use ZionBuilder\Utils;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Class Text
 *
 * @package ZionBuilder\Elements
 */
class Accordions extends Element {
	/**
	 * Get type
	 *
	 * Returns the unique id for the element
	 *
	 * @return string The element id/type
	 */
	public function get_type() {
		return 'accordions';
	}

	/**
	 * Get name
	 *
	 * Returns the name for the element
	 *
	 * @return string The element name
	 */
	public function get_name() {
		return __( 'Accordions', 'zionbuilder' );
	}

	/**
	 * Get keywords
	 *
	 * Returns the keywords for this element
	 *
	 * @return array The list of element keywords
	 */
	public function get_keywords() {
		return [ 'accordions' ];
	}

	/**
	 * Get Element Icon
	 *
	 * Returns the icon used in add elements panel for this element
	 *
	 * @return string The element icon
	 */
	public function get_element_icon() {
		return 'element-accordion';
	}

	/**
	 * Registers the element options
	 *
	 * @param Options $options The Options instance
	 *
	 * @return void
	 */
	public function options( $options ) {
		$options->add_option(
			'items',
			[
				'type'       => 'child_adder',
				'title'      => __( 'Accordions', 'zionbuilder' ),
				'child_type' => 'accordion_item',
				'item_name'  => 'title',
			]
		);

		$options->add_option(
			'type',
			[
				'type'             => 'select',
				'title'            => __( 'Type', 'zionbuilder' ),
				'default'          => 'accordion',
				'options'          => [
					[
						'name' => __( 'Accordion', 'zionbuilder' ),
						'id'   => 'accordion',
					],
					[
						'name' => __( 'Toogle', 'zionbuilder' ),
						'id'   => 'toggle',
					],
				],
				'render_attribute' => [
					[
						'attribute' => 'data-accordion-type',
					],
				],
			]
		);
	}

	/**
	 * Get style elements
	 *
	 * Returns a list of elements/tags that for which you
	 * want to show style options
	 */
	public function on_register_styles() {
		$this->register_style_options_element(
			'inner_content_styles_title',
			[
				'title'    => esc_html__( 'Title styles', 'zionbuilder' ),
				'selector' => '{{ELEMENT}} .zb-el-accordions-accordionTitle',
			]
		);

		$this->register_style_options_element(
			'inner_content_styles_content',
			[
				'title'    => esc_html__( 'Content styles', 'zionbuilder' ),
				'selector' => '{{ELEMENT}} .zb-el-accordions-accordionContent',
			]
		);
	}

	/**
	 * Enqueue element scripts for both frontend and editor
	 *
	 * If you want to use the ZionBuilder cache system you must use
	 * the enqueue_editor_script(), enqueue_element_script() functions
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Using helper methods will go through caching policy
		$this->enqueue_editor_script( Utils::get_file_url( 'dist/js/elements/Accordions/editor.js' ) );
		$this->enqueue_element_script( Utils::get_file_url( 'dist/js/elements/Accordions/frontend.js' ) );
	}

	/**
	 * Enqueue element styles for both frontend and editor
	 *
	 * If you want to use the ZionBuilder cache system you must use
	 * the enqueue_editor_style(), enqueue_element_style() functions
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		// Using helper methods will go through caching policy
		$this->enqueue_element_style( Utils::get_file_url( 'dist/css/elements/Accordions/frontend.css' ) );
	}

	/**
	 * Render
	 *
	 * Will render the element based on options
	 *
	 * @param Options $options
	 *
	 * @return void
	 */
	public function render( $options ) {
		$this->render_children();
	}

}
