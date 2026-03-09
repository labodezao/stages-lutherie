<?php
/**
 * Plugin Name: Stages Lutherie — Inscription REST API
 * Description: Endpoint REST pour recevoir les inscriptions du formulaire,
 *              envoyer un email au luthier (avec PDF joint) et un email de
 *              confirmation au stagiaire.
 * Version:     1.2
 * Author:      Labodezao
 *
 * INSTALLATION : copier ce fichier dans wp-content/mu-plugins/
 *
 * Endpoint exposé :
 *   POST /wp-json/stages-lutherie/v1/inscription
 *   Body JSON :
 *     fields       (object)  champs du formulaire
 *     pdfBase64    (string)  PDF récapitulatif encodé en base64
 *     planJson     (string)  JSON du plan de clavier (optionnel, si plan custom)
 *
 * Configuration (Réglages WordPress → Inscription Stage) :
 *   sl_luthier_email        email de destination (luthier)
 *   sl_bank_details         coordonnées bancaires
 *   sl_confirmation_subject objet de l'email de confirmation
 *   sl_confirmation_body    corps de l'email de confirmation
 *                           Variables : {nom}, {modele}, {acompte},
 *                                       {session}, {bank_details},
 *                                       {email}, {telephone}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ══════════════════════════════════════════════════════
   REST ENDPOINT
   ══════════════════════════════════════════════════════ */

add_action( 'rest_api_init', 'sl_register_inscription_route' );

function sl_register_inscription_route() {
	register_rest_route(
		'stages-lutherie/v1',
		'/inscription',
		array(
			'methods'             => 'POST',
			'callback'            => 'sl_handle_inscription',
			'permission_callback' => '__return_true',
		)
	);
}

function sl_handle_inscription( WP_REST_Request $request ) {
	$data       = $request->get_json_params();
	$fields     = isset( $data['fields'] )   ? (array) $data['fields']  : array();
	$pdf_base64 = isset( $data['pdfBase64'] ) ? (string) $data['pdfBase64'] : '';
	$plan_json  = isset( $data['planJson'] )  ? (string) $data['planJson']  : '';

	/* ── Sanitize required fields ── */
	$nom   = sanitize_text_field( isset( $fields['nom'] )   ? $fields['nom']   : '' );
	$email = sanitize_email(      isset( $fields['email'] ) ? $fields['email'] : '' );

	if ( empty( $nom ) || empty( $email ) || ! is_email( $email ) ) {
		return new WP_REST_Response(
			array( 'success' => false, 'message' => 'Nom et email valides requis.' ),
			400
		);
	}

	/* ── Sanitize other fields ── */
	$tel      = sanitize_text_field( isset( $fields['telephone'] )    ? $fields['telephone']    : '' );
	$modele   = sanitize_text_field( isset( $fields['modele'] )       ? $fields['modele']       : '' );
	$session  = sanitize_text_field( isset( $fields['session'] )      ? $fields['session']      : '' );
	$acompte  = sanitize_text_field( isset( $fields['acompte'] )      ? $fields['acompte']      : '' );
	$plan_lbl = sanitize_text_field( isset( $fields['planClavier'] )  ? $fields['planClavier']  : '' );

	/* ── WordPress option defaults ── */
	$defaults = array(
		'sl_luthier_email'        => 'contact@ewendaviau.com',
		'sl_bank_details'         => "IBAN : FR76 XXXX XXXX XXXX XXXX XXXX XXX\nBIC : XXXXXXXX\nTitulaire : Ewen Daviau",
		'sl_confirmation_subject' => "Confirmation d'inscription \u2014 Stage de lutherie",
		'sl_confirmation_body'    =>
			"Bonjour {nom},\n\n" .
			"Merci pour votre inscription au stage de lutherie !\n\n" .
			"Voici les informations pour valider votre inscription :\n\n" .
			"\u{1F4CB} Mod\u00e8le choisi : {modele}\n" .
			"\u{1F4B0} Acompte \u00e0 verser (40\u00a0%) : {acompte}\u00a0\u20ac\n" .
			"\u{1F4C5} Session : {session}\n\n" .
			"\u{1F3E6} Coordonn\u00e9es bancaires :\n{bank_details}\n\n" .
			"\u26a0\ufe0f Merci d'indiquer en r\u00e9f\u00e9rence de virement : STAGE-{nom}\n\n" .
			"Votre inscription sera confirm\u00e9e d\u00e8s r\u00e9ception de l'acompte.\n\n" .
			"\u00c0 tr\u00e8s bient\u00f4t \u00e0 l'atelier !\n\n" .
			"Ewen Daviau\n9 rue Fernand de Magellan\n44600 Saint-Nazaire\n" .
			"contact@ewendaviau.com\newendaviau.com",
	);

	$luthier_email = get_option( 'sl_luthier_email', $defaults['sl_luthier_email'] );
	$bank_details  = get_option( 'sl_bank_details',  $defaults['sl_bank_details']  );
	$conf_subject  = get_option( 'sl_confirmation_subject', $defaults['sl_confirmation_subject'] );
	$conf_body     = get_option( 'sl_confirmation_body',    $defaults['sl_confirmation_body']    );

	/* ── Decode PDF to temp file ── */
	$attachments = array();
	$pdf_path    = '';

	if ( ! empty( $pdf_base64 ) ) {
		$pdf_data = base64_decode( $pdf_base64, true );
		if ( $pdf_data !== false ) {
			$pdf_path = wp_tempnam( 'inscription_' . sanitize_file_name( $nom ) . '.pdf' );
			// Ensure the file ends with .pdf so mail clients recognise the attachment.
			$pdf_path_pdf = $pdf_path . '.pdf';
			if ( file_put_contents( $pdf_path_pdf, $pdf_data ) !== false ) {
				$attachments[] = $pdf_path_pdf;
			}
		}
	}

	/* ── Attach plan JSON if custom plan ── */
	$json_path = '';
	if ( ! empty( $plan_json ) ) {
		$json_path = wp_tempnam( 'plan_clavier_' . sanitize_file_name( $nom ) ) . '.json';
		if ( file_put_contents( $json_path, $plan_json ) !== false ) {
			$attachments[] = $json_path;
		}
	}

	/* ── Build all field lines for luthier email ── */
	$field_lines = "Nouvelle inscription re\u00e7ue :\n\n";
	foreach ( $fields as $key => $val ) {
		if ( ! empty( $val ) ) {
			$field_lines .= sanitize_text_field( $key ) . ' : ' . sanitize_text_field( (string) $val ) . "\n";
		}
	}
	if ( ! empty( $plan_json ) ) {
		$field_lines .= "\n\u{1F5C3}\ufe0f Le fichier JSON du plan de clavier personnalis\u00e9 est joint en pi\u00e8ce jointe.";
	}
	$field_lines .= "\n\nLe r\u00e9capitulatif PDF est joint.";

	/* ── Email to luthier ── */
	$luthier_subject = "Nouvelle inscription stage \u2014 " . $nom;
	$headers_luthier = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $email,
	);

	wp_mail(
		$luthier_email,
		$luthier_subject,
		$field_lines,
		$headers_luthier,
		$attachments
	);

	/* ── Confirmation email to trainee ── */
	$replacements = array(
		'{nom}'          => $nom,
		'{modele}'       => $modele,
		'{acompte}'      => $acompte,
		'{session}'      => $session,
		'{bank_details}' => $bank_details,
		'{email}'        => $email,
		'{telephone}'    => $tel,
	);
	$conf_body_filled    = str_replace( array_keys( $replacements ), array_values( $replacements ), $conf_body );
	$conf_subject_filled = str_replace( array_keys( $replacements ), array_values( $replacements ), $conf_subject );

	$headers_conf = array( 'Content-Type: text/plain; charset=UTF-8' );

	wp_mail( $email, $conf_subject_filled, $conf_body_filled, $headers_conf );

	/* ── Cleanup temp files ── */
	foreach ( array( $pdf_path, $pdf_path_pdf ?? '', $json_path ) as $tmp ) {
		if ( ! empty( $tmp ) && file_exists( $tmp ) ) {
			unlink( $tmp );
		}
	}

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => 'Inscription envoy\u00e9e avec succ\u00e8s !',
		),
		200
	);
}

/* ══════════════════════════════════════════════════════
   ADMIN SETTINGS PAGE
   ══════════════════════════════════════════════════════ */

add_action( 'admin_menu', 'sl_add_settings_page' );

function sl_add_settings_page() {
	add_options_page(
		'Inscription Stage',
		'Inscription Stage',
		'manage_options',
		'sl_inscription',
		'sl_render_settings_page'
	);
}

add_action( 'admin_init', 'sl_register_settings' );

function sl_register_settings() {
	register_setting( 'sl_inscription', 'sl_luthier_email',        array( 'sanitize_callback' => 'sanitize_email' ) );
	register_setting( 'sl_inscription', 'sl_bank_details',         array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'sl_inscription', 'sl_confirmation_subject', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'sl_inscription', 'sl_confirmation_body',    array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
}

function sl_render_settings_page() {
	$defaults = array(
		'sl_luthier_email'        => 'contact@ewendaviau.com',
		'sl_bank_details'         => "IBAN : FR76 XXXX XXXX XXXX XXXX XXXX XXX\nBIC : XXXXXXXX\nTitulaire : Ewen Daviau",
		'sl_confirmation_subject' => "Confirmation d'inscription — Stage de lutherie",
		'sl_confirmation_body'    => "Bonjour {nom},\n\nMerci pour votre inscription !",
	);
	?>
	<div class="wrap">
		<h1>Réglages inscription stage</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'sl_inscription' ); ?>
			<table class="form-table">
				<tr>
					<th>Email luthier (destinataire)</th>
					<td><input type="email" name="sl_luthier_email" value="<?php echo esc_attr( get_option( 'sl_luthier_email', $defaults['sl_luthier_email'] ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th>Coordonnées bancaires</th>
					<td><textarea name="sl_bank_details" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'sl_bank_details', $defaults['sl_bank_details'] ) ); ?></textarea></td>
				</tr>
				<tr>
					<th>Objet du mail de confirmation</th>
					<td><input type="text" name="sl_confirmation_subject" value="<?php echo esc_attr( get_option( 'sl_confirmation_subject', $defaults['sl_confirmation_subject'] ) ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th>Corps du mail de confirmation</th>
					<td>
						<textarea name="sl_confirmation_body" rows="16" class="large-text"><?php echo esc_textarea( get_option( 'sl_confirmation_body', $defaults['sl_confirmation_body'] ) ); ?></textarea>
						<p class="description">Variables : {nom}, {modele}, {acompte}, {session}, {bank_details}, {email}, {telephone}</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
