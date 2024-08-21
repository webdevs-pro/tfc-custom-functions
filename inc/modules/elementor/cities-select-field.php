<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Icons_Manager;

class Elementor_Form_Cities_Select extends \ElementorPro\Modules\Forms\Fields\Field_Base {

	public function get_type() {
		return 'cities-select';
	}

	public function get_name() {
		return 'Cities Select';
	}

	public function render( $item, $item_index, $form ) {
		$form->add_render_attribute(
			[
				'select-wrapper' . $item_index => [
					'class' => [
						'elementor-field',
						'elementor-select-wrapper',
						'remove-before',
						esc_attr( $item['css_classes'] ),
					],
				],
				'select' . $item_index => [
					'name' => $form->get_attribute_name( $item ) . ( ! empty( $item['allow_multiple'] ) ? '[]' : '' ),
					'id' => $form->get_attribute_id( $item ),
					'class' => [
						'elementor-field-textual',
						'elementor-size-' . $item['input_size'],
					],
				],
			]
		);

		if ( $item['required'] ) {
			$form->add_render_attribute( 'select' . $item_index, 'required', 'required' );
			$form->add_render_attribute( 'select' . $item_index, 'aria-required', 'true' );
		}

		echo '<div class="elementor-field elementor-select-wrapper">';

		?>
		<div <?php $form->print_render_attribute_string( 'select-wrapper' . $item_index ); ?>>

			<select <?php $form->print_render_attribute_string( 'select' . $item_index ); ?> style="appearance: auto;">
				<?php

				$args = array(
					'taxonomy'   => 'origin-city',
					'hide_empty' => false,
				);

				$city_terms = get_terms( $args );

				$selected_city = isset( $_GET['signup-city'] ) ? $_GET['signup-city'] : '';
				$city_found = false;
				
				foreach ( $city_terms as $city_term ) {
					 if ( $selected_city === $city_term->name ) {
						  $city_found = true;
						  break;
					 }
				}
				
				if ( ! $city_found ) {
					 echo '<option value="" hidden selected>' . esc_html( $item['cities-select-placeholder'] ) . '</option>';
				}
				
				foreach ( $city_terms as $city_term ) {
					 $selected = selected( $selected_city, $city_term->name, false );
					 echo '<option value="' . esc_attr( $city_term->name ) . '"' . $selected . '>' . esc_html( $city_term->name ) . '</option>';
				}

			echo '</select>';
		echo '</div>';
	}

	public function update_controls( $widget ) {
		$elementor = \ElementorPro\Plugin::elementor();

		$control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );

		if ( is_wp_error( $control_data ) ) {
			return;
		}

		$field_controls = [
			'cities-select-placeholder' => [
				'name' => 'cities-select-placeholder',
				'label' => esc_html__( 'Placeholder', 'elementor' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'dynamic' => [
					'active' => false,
				],
				'ai' => [
					'active' => false,
				],
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'tab'          => 'content',
				'inner_tab'    => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
		];

		$control_data['fields'] = $this->inject_field_controls( $control_data['fields'], $field_controls );

		$widget->update_control( 'form_fields', $control_data );
	}

}
