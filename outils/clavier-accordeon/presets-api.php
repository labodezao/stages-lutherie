<?php
/**
 * Plugin Name: Clavier Accordéon — Presets REST API
 * Description: Endpoint REST pour créer/modifier les présets de clavier accordéon
 *              directement depuis l'outil admin (sans FTP).
 * Version:     1.3
 * Author:      Labodezao
 *
 * INSTALLATION :
 *   1. Copier ce fichier dans wp-content/mu-plugins/
 *   2. Uploader ca-svg.js dans wp-content/uploads/presets/ca-svg.js
 *      (source : outils/clavier-accordeon/ca-svg.js dans le dépôt)
 *   3. Uploader le dossier presets/ (index.json + *.json) dans wp-content/uploads/presets/
 *
 * Endpoints exposés :
 *   POST   /wp-json/clavier-accordeon/v1/save-preset
 *   DELETE /wp-json/clavier-accordeon/v1/delete-preset
 *   POST   /wp-json/clavier-accordeon/v1/update-preset-meta
 *
 * Script global chargé sur toutes les pages :
 *   ca-svg.js  (doit être déposé dans wp-content/uploads/presets/)
 *
 * Droits requis : upload_files (éditeur / administrateur).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ca_register_presets_routes' ) ) :

add_action( 'rest_api_init', 'ca_register_presets_routes' );

function ca_register_presets_routes() {
	register_rest_route(
		'clavier-accordeon/v1',
		'/save-preset',
		array(
			'methods'             => 'POST',
			'callback'            => 'ca_rest_save_preset',
			'permission_callback' => 'ca_rest_can_save_preset',
			'args'                => array(
				'id'       => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_file_name',
					'validate_callback' => function ( $v ) {
						return (bool) preg_match( '/^[a-zA-Z0-9_\-]{1,80}$/', $v );
					},
					'description'       => 'Identifiant du préset (alphanumérique, tirets, underscores).',
				),
				'label'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Nom affiché dans la liste des présets.',
				),
				'category' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'Famille / catégorie du préset.',
				),
				'data'     => array(
					'required'    => true,
					'description' => 'Objet JSON complet du préset.',
				),
			),
		)
	);
}

endif; // function_exists ca_register_presets_routes

if ( ! function_exists( 'ca_rest_can_save_preset' ) ) :

function ca_rest_can_save_preset() {
	return current_user_can( 'upload_files' );
}

endif; // function_exists ca_rest_can_save_preset

if ( ! function_exists( 'ca_rest_save_preset' ) ) :

function ca_rest_save_preset( WP_REST_Request $req ) {
	$id       = $req->get_param( 'id' );
	$label    = $req->get_param( 'label' );
	$category = $req->get_param( 'category' );
	$data     = $req->get_param( 'data' );

	/* ── Répertoire presets/ dans uploads/ ── */
	$upload    = wp_upload_dir();
	$presets_dir = trailingslashit( $upload['basedir'] ) . 'presets/';

	if ( ! is_dir( $presets_dir ) ) {
		if ( ! wp_mkdir_p( $presets_dir ) ) {
			return new WP_Error(
				'mkdir_error',
				'Impossible de créer le répertoire presets/.',
				array( 'status' => 500 )
			);
		}
	}

	// Validate that the constructed paths stay within the uploads directory.
	$presets_dir_real = realpath( $presets_dir );
	if ( $presets_dir_real === false ) {
		return new WP_Error( 'dir_error', 'Répertoire presets/ introuvable.', array( 'status' => 500 ) );
	}
	$preset_path = $presets_dir_real . DIRECTORY_SEPARATOR . $id . '.json';
	$index_path  = $presets_dir_real . DIRECTORY_SEPARATOR . 'index.json';

	// Guard against directory traversal: ensure paths stay inside presets_dir.
	if ( strpos( $preset_path, $presets_dir_real . DIRECTORY_SEPARATOR ) !== 0 ) {
		return new WP_Error( 'invalid_path', 'Chemin de fichier non autorisé.', array( 'status' => 400 ) );
	}

	/* ── Écriture du fichier {id}.json (création ou écrasement) ── */
	$encoded     = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

	if ( $encoded === false ) {
		return new WP_Error(
			'encode_error',
			'Impossible d\'encoder les données JSON.',
			array( 'status' => 500 )
		);
	}

	if ( file_put_contents( $preset_path, $encoded ) === false ) {
		return new WP_Error(
			'write_error',
			'Impossible d\'écrire le fichier ' . $id . '.json.',
			array( 'status' => 500 )
		);
	}

	/* ── Mise à jour de index.json ── */
	$index = array();

	if ( file_exists( $index_path ) ) {
		$raw = file_get_contents( $index_path );
		if ( $raw === false ) {
			return new WP_Error( 'read_error', 'Impossible de lire index.json.', array( 'status' => 500 ) );
		}
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$index = $decoded;
		}
	}

	$found = false;
	for ( $i = 0; $i < count( $index ); $i++ ) {
		if ( isset( $index[ $i ]['id'] ) && $index[ $i ]['id'] === $id ) {
			$index[ $i ]['label']    = $label;
			$index[ $i ]['category'] = $category;
			$found                   = true;
			break;
		}
	}

	if ( ! $found ) {
		$index[] = array(
			'id'       => $id,
			'label'    => $label,
			'category' => $category,
		);
	}

	$idx_encoded = wp_json_encode( $index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	if ( $idx_encoded === false || file_put_contents( $index_path, $idx_encoded ) === false ) {
		return new WP_Error(
			'index_error',
			'Impossible de mettre à jour index.json.',
			array( 'status' => 500 )
		);
	}

	return rest_ensure_response(
		array(
			'success'  => true,
			'id'       => $id,
			'label'    => $label,
			'category' => $category,
			'url'      => trailingslashit( $upload['baseurl'] ) . 'presets/' . $id . '.json',
		)
	);
}

endif; // function_exists ca_rest_save_preset

/* ══════════════════════════════════════════════════════
   DELETE PRESET
   DELETE /wp-json/clavier-accordeon/v1/delete-preset
   Param: id (string) — identifiant du préset
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'ca_register_delete_route' ) ) :

add_action( 'rest_api_init', 'ca_register_delete_route' );

function ca_register_delete_route() {
	register_rest_route(
		'clavier-accordeon/v1',
		'/delete-preset',
		array(
			'methods'             => 'DELETE',
			'callback'            => 'ca_rest_delete_preset',
			'permission_callback' => 'ca_rest_can_save_preset',
			'args'                => array(
				'id' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_file_name',
					'validate_callback' => function ( $v ) {
						return (bool) preg_match( '/^[a-zA-Z0-9_\-]{1,80}$/', $v );
					},
				),
			),
		)
	);
}

endif; // function_exists ca_register_delete_route

if ( ! function_exists( 'ca_rest_delete_preset' ) ) :

function ca_rest_delete_preset( WP_REST_Request $req ) {
	$id = $req->get_param( 'id' );

	$upload      = wp_upload_dir();
	$presets_dir = realpath( trailingslashit( $upload['basedir'] ) . 'presets/' );
	if ( ! $presets_dir ) {
		return new WP_Error( 'dir_error', 'Répertoire presets/ introuvable.', array( 'status' => 500 ) );
	}

	$preset_path = $presets_dir . DIRECTORY_SEPARATOR . $id . '.json';
	$index_path  = $presets_dir . DIRECTORY_SEPARATOR . 'index.json';

	// Guard against directory traversal.
	if ( strpos( $preset_path, $presets_dir . DIRECTORY_SEPARATOR ) !== 0 ) {
		return new WP_Error( 'invalid_path', 'Chemin non autorisé.', array( 'status' => 400 ) );
	}

	// Delete the JSON file (ignore if already gone).
	if ( file_exists( $preset_path ) ) {
		if ( ! unlink( $preset_path ) ) {
			return new WP_Error( 'delete_error', 'Impossible de supprimer ' . $id . '.json.', array( 'status' => 500 ) );
		}
	}

	// Remove entry from index.json.
	if ( file_exists( $index_path ) ) {
		$raw   = file_get_contents( $index_path );
		$index = $raw !== false ? json_decode( $raw, true ) : array();
		if ( is_array( $index ) ) {
			$index = array_values( array_filter( $index, function ( $e ) use ( $id ) {
				return ! ( isset( $e['id'] ) && $e['id'] === $id );
			} ) );
			file_put_contents( $index_path, wp_json_encode( $index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		}
	}

	return rest_ensure_response( array( 'success' => true, 'id' => $id ) );
}

endif; // function_exists ca_rest_delete_preset

/* ══════════════════════════════════════════════════════
   UPDATE PRESET META (rename label / change category)
   POST /wp-json/clavier-accordeon/v1/update-preset-meta
   Params: id, label, category
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'ca_register_update_meta_route' ) ) :

add_action( 'rest_api_init', 'ca_register_update_meta_route' );

function ca_register_update_meta_route() {
	register_rest_route(
		'clavier-accordeon/v1',
		'/update-preset-meta',
		array(
			'methods'             => 'POST',
			'callback'            => 'ca_rest_update_preset_meta',
			'permission_callback' => 'ca_rest_can_save_preset',
			'args'                => array(
				'id'       => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_file_name',
					'validate_callback' => function ( $v ) {
						return (bool) preg_match( '/^[a-zA-Z0-9_\-]{1,80}$/', $v );
					},
				),
				'label'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'category' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}

endif; // function_exists ca_register_update_meta_route

if ( ! function_exists( 'ca_rest_update_preset_meta' ) ) :

function ca_rest_update_preset_meta( WP_REST_Request $req ) {
	$id       = $req->get_param( 'id' );
	$label    = $req->get_param( 'label' );
	$category = $req->get_param( 'category' );

	$upload      = wp_upload_dir();
	$presets_dir = realpath( trailingslashit( $upload['basedir'] ) . 'presets/' );
	if ( ! $presets_dir ) {
		return new WP_Error( 'dir_error', 'Répertoire presets/ introuvable.', array( 'status' => 500 ) );
	}

	$preset_path = $presets_dir . DIRECTORY_SEPARATOR . $id . '.json';
	$index_path  = $presets_dir . DIRECTORY_SEPARATOR . 'index.json';

	// Guard against directory traversal.
	if ( strpos( $preset_path, $presets_dir . DIRECTORY_SEPARATOR ) !== 0 ) {
		return new WP_Error( 'invalid_path', 'Chemin non autorisé.', array( 'status' => 400 ) );
	}

	if ( ! file_exists( $preset_path ) ) {
		return new WP_Error( 'not_found', 'Préset ' . $id . ' introuvable.', array( 'status' => 404 ) );
	}

	// Update the preset JSON file itself (nom field).
	$raw_preset = file_get_contents( $preset_path );
	if ( $raw_preset !== false ) {
		$preset_data = json_decode( $raw_preset, true );
		if ( is_array( $preset_data ) ) {
			$preset_data['nom'] = $label;
			file_put_contents( $preset_path, wp_json_encode( $preset_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		}
	}

	// Update index.json.
	if ( ! file_exists( $index_path ) ) {
		return new WP_Error( 'index_missing', 'index.json introuvable.', array( 'status' => 500 ) );
	}
	$raw   = file_get_contents( $index_path );
	$index = is_string( $raw ) ? json_decode( $raw, true ) : array();
	if ( ! is_array( $index ) ) {
		return new WP_Error( 'index_error', 'index.json invalide.', array( 'status' => 500 ) );
	}
	$found = false;
	foreach ( $index as &$entry ) {
		if ( isset( $entry['id'] ) && $entry['id'] === $id ) {
			$entry['label']    = $label;
			$entry['category'] = $category;
			$found             = true;
			break;
		}
	}
	unset( $entry );
	if ( ! $found ) {
		return new WP_Error( 'not_in_index', 'Préset ' . $id . ' absent de index.json.', array( 'status' => 404 ) );
	}
	file_put_contents( $index_path, wp_json_encode( $index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

	return rest_ensure_response( array( 'success' => true, 'id' => $id, 'label' => $label, 'category' => $category ) );
}

endif; // function_exists ca_rest_update_preset_meta

/* ══════════════════════════════════════════════════════
   NONCE INJECTION — makes window.CA_SAVE_NONCE available
   to the admin widget JavaScript so the REST fetch can
   pass X-WP-Nonce and pass the permission_callback check.
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'ca_output_save_nonce' ) ) :

function ca_output_save_nonce() {
	if ( current_user_can( 'upload_files' ) ) {
		echo '<script>window.CA_SAVE_NONCE=' . wp_json_encode( wp_create_nonce( 'wp_rest' ) ) . ';</script>' . "\n";
	}
}
add_action( 'wp_footer',    'ca_output_save_nonce', 1 );
add_action( 'admin_footer', 'ca_output_save_nonce', 1 );

endif; // function_exists ca_output_save_nonce

/* ══════════════════════════════════════════════════════
   SHARED SVG LIBRARY — enqueue ca-svg.js on all pages
   (must be deployed to wp-content/uploads/presets/)
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'ca_enqueue_svg_lib' ) ) :

function ca_enqueue_svg_lib() {
	$upload   = wp_upload_dir();
	$svg_path = trailingslashit( $upload['basedir'] ) . 'presets/ca-svg.js';
	if ( ! file_exists( $svg_path ) ) {
		return; // not deployed yet — graceful no-op
	}
	$ver = (string) filemtime( $svg_path );
	wp_enqueue_script(
		'ca-svg',
		trailingslashit( $upload['baseurl'] ) . 'presets/ca-svg.js',
		array(),
		$ver,
		false // load in <head> so it's available before inline blocks run
	);
}
add_action( 'wp_enqueue_scripts',    'ca_enqueue_svg_lib' );
add_action( 'admin_enqueue_scripts', 'ca_enqueue_svg_lib' );

endif; // function_exists ca_enqueue_svg_lib
