<?php

add_action( 'init', 'metrocorp_initialize_cmb_meta_boxes', 9999 );
/**
 * Initialize the metabox class.
 */
function metrocorp_initialize_cmb_meta_boxes() {

	if ( ! class_exists( 'cmb_Meta_Box' ) )
		require_once 'cmb/init.php';

}

add_filter( 'cmb_meta_boxes', 'metrocorp_cmb_setup', 99 );
function metrocorp_cmb_setup( array $meta_boxes ) {

	$prefix = '_metrocorp_';

	$meta_boxes[] = metrocorp_build_meta_box( 'restaurant' );
	$meta_boxes[] = metrocorp_build_meta_box( 'wedding' );
	$meta_boxes[] = metrocorp_build_meta_box( 'shopping', 'shopping' );
	$meta_boxes[] = metrocorp_build_meta_box( 'home', 'home' );
	$single = 'doctor';
	$plural = $single .'s';
	$prefix = $prefix . $plural .'_';
	$args = array(
		'name' => metrocorp_meta_name( $prefix, $single ),
		'practice_name' => metrocorp_meta_practice( $prefix ),
		'link' => metrocorp_meta_link( $prefix, $single, array( 'desc' => 'enter URL to '. $single .' site.' ) ),
		'phone' => metrocorp_meta_phone( $prefix ),
	);
	$meta_boxes[] = metrocorp_build_meta_box( $single, '', $args, false );

	$single = 'dentist';
	$plural = $single .'s';
	$prefix = $prefix . $plural .'_';
	$args = array(
		'name' => metrocorp_meta_name( $prefix, $single ),
		'practice_name' => metrocorp_meta_practice( $prefix ),
		'link' => metrocorp_meta_link( $prefix, $single, array( 'desc' => 'enter URL to '. $single .' site.' ) ),
		'phone' => metrocorp_meta_phone( $prefix ),
	);
	$meta_boxes[] = metrocorp_build_meta_box( $single, '', $args, false );

	return $meta_boxes;
}

function metrocorp_build_meta_box( $single = '', $plural = '', $custom_args = array(), $parse = true ) {

	$prefix = '_metrocorp_';
	if ( empty( $plural ) ) $plural = $single .'s';
	$prefix = $prefix . $plural .'_';

	$defaults = array(
		'link' => metrocorp_meta_link( $prefix, $single, array( 'desc' => 'enter URL to '. $single .' site, menu or booking service' ) ),
		'phone' => metrocorp_meta_phone( $prefix ),
		'address' => metrocorp_meta_address( $prefix ),
		'latitude' => metrocorp_meta_latitude( $prefix ),
		'longitude' => metrocorp_meta_longitude( $prefix ),
	);
	if ( $parse === true ) {
		$args = wp_parse_args( $custom_args, $defaults );
	} else {
		$args = $custom_args;
	}

	return array(
		'id'         => $single .'-cpt',
		'title'      => ucwords( $single ) .' Info',
		'pages'      => array( $plural, ),
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true,
		'fields'     => $args,
	);
}

function metrocorp_meta_link( $prefix = '', $single = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Link',
		'desc' => 'enter URL to '. $single .' site',
		'id'   => $prefix . 'url',
		'type' => 'text',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_phone( $prefix = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Phone',
		'id'   => $prefix . 'phone',
		'type' => 'text_small',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_address( $prefix = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Address',
		'id'   => $prefix . 'address',
		'type' => 'textarea_small',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_latitude( $prefix = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Latitude',
		'id'   => $prefix . 'latitude',
		'type' => 'text_small',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_longitude( $prefix = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Longitude',
		'id'   => $prefix . 'longitude',
		'type' => 'text_small',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_name( $prefix = '', $single = '', $custom_args = array() ) {

	$single = ( !empty( $single ) ) ? ucfirst( $single ) .'\'s ' : '';
	$defaults = array(
		'name' => $single .'Name',
		'id'   => $prefix . 'name',
		'type' => 'text_medium',
	);
	return wp_parse_args( $custom_args, $defaults );
}

function metrocorp_meta_practice( $prefix = '', $custom_args = array() ) {
	$defaults = array(
		'name' => 'Practice Name',
		'id'   => $prefix . 'practice',
		'type' => 'text_medium',
	);
	return wp_parse_args( $custom_args, $defaults );
}
