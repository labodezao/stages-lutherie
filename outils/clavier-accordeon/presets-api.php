<?php
/**
 * Plugin Name: Clavier Accordéon — Presets REST API
 * Description: Endpoint REST pour créer/modifier les présets de clavier accordéon
 *              directement depuis l'outil admin (sans FTP).
 * Version:     1.1
 * Author:      Labodezao
 *
 * INSTALLATION : copier ce fichier dans wp-content/mu-plugins/
 *
 * Endpoint exposé :
 *   POST /wp-json/clavier-accordeon/v1/save-preset
 *   Paramètres JSON :
 *     id       (string) identifiant du préset, ex. "GCD33"  (lettres, chiffres, - _)
 *     label    (string) nom affiché, ex. "Sol/Do — 33 boutons"
 *     category (string) famille, ex. "3 rangs 12b"
 *     data     (object) contenu complet du préset (sera écrit dans {id}.json)
 *
 *   Crée le fichier s'il n'existe pas, l'écrase sinon.
 *   Met également à jour (ou ajoute) l'entrée correspondante dans index.json.
 *
 * Droits requis : upload_files (éditeur / administrateur).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

function ca_rest_can_save_preset() {
	return current_user_can( 'upload_files' );
}

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

	/* ── Écriture du fichier {id}.json (création ou écrasement) ── */
	$preset_path = $presets_dir . $id . '.json';
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
	$index_path = $presets_dir . 'index.json';
	$index      = array();

	if ( file_exists( $index_path ) ) {
		$raw     = file_get_contents( $index_path );
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$index = $decoded;
		}
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
