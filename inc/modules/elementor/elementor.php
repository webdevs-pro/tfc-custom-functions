<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add new `local-tel` field to Elementor form widget.
 *
 * @since 1.0.0
 * @param \ElementorPro\Modules\Forms\Registrars\Form_Fields_Registrar $form_fields_registrar
 * @return void
 */
function add_new_form_field( $form_fields_registrar ) {

	require_once( __DIR__ . '/cities-select-field.php' );

	$form_fields_registrar->register( new \Elementor_Form_Cities_Select() );
}
add_action( 'elementor_pro/forms/fields/register', 'add_new_form_field' );