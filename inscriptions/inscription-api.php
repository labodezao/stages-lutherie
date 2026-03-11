<?php
/**
 * Plugin Name: Stages Lutherie — Inscription REST API
 * Description: Endpoint REST pour recevoir les inscriptions du formulaire,
 *              envoyer un email au luthier (avec PDF + JSON joints) et un
 *              email de confirmation au stagiaire (avec PDF + JSON joints).
 * Version:     1.6
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
 *   stluth_luthier_email        email de destination (luthier)
 *   stluth_bank_details         coordonnées bancaires (injectées via {bank_details})
 *   stluth_confirmation_subject objet de l'email de confirmation
 *   stluth_confirmation_body    corps HTML complet de l'email de confirmation
 *                               (modifiable depuis l'admin WP sans FTP)
 *                           Variables : {nom}, {modele}, {acompte},
 *                                       {session}, {bank_details},
 *                                       {email}, {telephone}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ══════════════════════════════════════════════════════
   HELPER — Default HTML email template
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_default_email_html' ) ) :
function stluth_default_email_html() {
	return '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirmation d\'inscription — Stage de fabrication d\'accordéon</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f0eb;font-family:Georgia,\'Times New Roman\',serif;">

<!--[if mso]><table width="600" align="center" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f0eb;padding:30px 10px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.12);">

        <!-- EN-TÊTE -->
        <tr>
          <td style="background-color:#3E2723;padding:32px 40px;text-align:center;">
            <p style="margin:0 0 4px 0;font-family:Georgia,serif;font-size:10px;letter-spacing:4px;text-transform:uppercase;color:#D4A017;">✦ Stage de lutherie · 2026 ✦</p>
            <h1 style="margin:8px 0 6px 0;font-family:Georgia,serif;font-size:26px;font-weight:normal;color:#ffffff;letter-spacing:1px;">Ewen Daviau</h1>
            <p style="margin:0;font-family:Georgia,serif;font-size:13px;color:#F5D061;font-style:italic;">Fabrication d\'accordéons diatoniques</p>
          </td>
        </tr>

        <!-- BANDEAU TITRE -->
        <tr>
          <td style="background-color:#D4A017;padding:12px 40px;text-align:center;">
            <p style="margin:0;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#3E2723;font-weight:bold;">Confirmation d\'inscription</p>
          </td>
        </tr>

        <!-- SALUTATION -->
        <tr>
          <td style="padding:36px 40px 20px 40px;">
            <p style="margin:0 0 16px 0;font-size:16px;color:#2c2c2c;line-height:1.6;">Bonjour <strong>{nom}</strong>,</p>
            <p style="margin:0;font-size:15px;color:#2c2c2c;line-height:1.7;">
              Merci pour votre inscription au stage de fabrication d\'accordéon diatonique&nbsp;!
              Votre demande a bien été reçue. Voici le récapitulatif de votre inscription
              ainsi que les informations nécessaires pour la valider.
            </p>
          </td>
        </tr>

        <!-- RÉCAPITULATIF -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#faf7f3;border:1px solid #e0d4c4;border-radius:4px;overflow:hidden;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">Récapitulatif</p>
                </td>
              </tr>
              <tr>
                <td style="padding:20px;">
                  <table role="presentation" width="100%" cellpadding="6" cellspacing="0">
                    <tr>
                      <td style="width:46%;font-size:13px;color:#7a6a55;font-family:Georgia,serif;vertical-align:top;">Modèle&nbsp;choisi</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;vertical-align:top;">{modele}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Session</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{session}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Acompte&nbsp;(40&nbsp;%)</td>
                      <td style="font-size:16px;color:#3E2723;font-weight:bold;padding-top:10px;vertical-align:top;">{acompte}&nbsp;€</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- COORDONNÉES BANCAIRES -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fdf8ee;border-left:4px solid #D4A017;border-radius:0 4px 4px 0;">
              <tr>
                <td style="padding:18px 20px;">
                  <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:#7a6a55;font-family:Georgia,serif;">Coordonnées bancaires</p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.8;">{bank_details}</p>
                  <p style="margin:14px 0 0 0;font-size:13px;color:#5a4a35;line-height:1.6;">
                    ⚠️ Merci d\'indiquer en référence de virement&nbsp;:
                    <strong style="color:#3E2723;">STAGE-{nom}</strong>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- INFORMATIONS PRATIQUES -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#f0ece6;border-radius:4px;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;border-radius:4px 4px 0 0;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">🎵 Votre accordéon</p>
                </td>
              </tr>
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    Pendant ces 10 jours, vous fabriquerez votre accordéon diatonique de A à Z&nbsp;:
                    découpe et assemblage de la caisse, montage du sommier et du mécanisme,
                    pose et réglage des anches, fabrication du soufflet, et finitions.
                    Vous repartez avec <strong>votre propre instrument</strong>.
                  </p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    Aucun prérequis en menuiserie n\'est nécessaire — juste de la curiosité et de la motivation&nbsp;!
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- PIÈCES JOINTES -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fdf8ee;border:1px solid #e0d4c4;border-radius:4px;">
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:#7a6a55;font-family:Georgia,serif;">📎 Pièces jointes</p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.8;">
                    📄 <strong>PDF récapitulatif</strong> — le détail complet de votre inscription<br>
                    🎹 <strong>Plan de clavier</strong> (JSON) — la disposition des notes de votre accordéon
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- TEXTE INFO -->
        <tr>
          <td style="padding:0 40px 32px 40px;">
            <p style="margin:0 0 12px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              Votre inscription sera définitivement confirmée dès réception de l\'acompte.
            </p>
            <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              Pour toute question, n\'hésitez pas à me contacter&nbsp;:
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- SIGNATURE -->
        <tr>
          <td style="padding:0 40px 36px 40px;border-top:1px solid #e0d4c4;">
            <p style="margin:24px 0 4px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">À très bientôt à l\'atelier&nbsp;!</p>
            <p style="margin:0 0 4px 0;font-size:15px;color:#3E2723;font-family:Georgia,serif;font-weight:bold;">Ewen Daviau</p>
            <p style="margin:0;font-size:13px;color:#7a6a55;line-height:1.7;">
              9 rue Fernand de Magellan — 44600 Saint-Nazaire<br>
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a> —
              <a href="https://ewendaviau.com" style="color:#D4A017;text-decoration:none;">ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- PIED DE PAGE -->
        <tr>
          <td style="background-color:#f0e8dc;padding:16px 40px;text-align:center;border-top:1px solid #e0d4c4;">
            <p style="margin:0;font-size:11px;color:#a09080;line-height:1.6;">
              Cet email vous a été envoyé suite à votre inscription sur
              <a href="https://ewendaviau.com" style="color:#a09080;">ewendaviau.com</a>.
              Vous pouvez contacter l\'atelier à tout moment pour modifier ou annuler votre inscription.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->

</body>
</html>';
}
endif; // function_exists stluth_default_email_html

/* ══════════════════════════════════════════════════════
   REST ENDPOINT
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_register_inscription_route' ) ) :

add_action( 'rest_api_init', 'stluth_register_inscription_route', 99 );

function stluth_register_inscription_route() {
	register_rest_route(
		'stages-lutherie/v1',
		'/inscription',
		array(
			'methods'             => 'POST',
			'callback'            => 'stluth_handle_inscription',
			'permission_callback' => '__return_true',
		)
	);
}

endif; // function_exists stluth_register_inscription_route

if ( ! function_exists( 'stluth_handle_inscription' ) ) :
function stluth_handle_inscription( WP_REST_Request $request ) {
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
		'stluth_luthier_email'        => 'contact@ewendaviau.com',
		'stluth_bank_details'         => "IBAN : FR76 1380 7008 7907 0218 7398 930\nBIC : CCBPFRPPNAN\nTitulaire : Ewen Daviau",
		'stluth_confirmation_subject' => 'Confirmation d\'inscription — Stage de fabrication d\'accordéon',
	);

	$luthier_email = get_option( 'stluth_luthier_email', $defaults['stluth_luthier_email'] );
	$bank_details  = get_option( 'stluth_bank_details',  $defaults['stluth_bank_details']  );
	$conf_subject  = get_option( 'stluth_confirmation_subject', $defaults['stluth_confirmation_subject'] );

	/* HTML body : WP option (primary) → FTP file (legacy fallback) → built-in default */
	$stored_body = get_option( 'stluth_confirmation_body', '' );
	if ( ! empty( $stored_body ) ) {
		$html_tpl = $stored_body;
	} elseif ( file_exists( __DIR__ . '/email-confirmation-stagiaire.html' ) ) {
		$html_tpl = file_get_contents( __DIR__ . '/email-confirmation-stagiaire.html' );
	} else {
		$html_tpl = stluth_default_email_html();
	}

	/* ── Decode PDF to temp file ── */
	$attachments  = array();
	$pdf_path     = '';
	$pdf_path_pdf = '';

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
	$json_path     = '';
	$json_tmp_base = ''; /* bare file created by wp_tempnam (needs separate cleanup) */
	if ( ! empty( $plan_json ) ) {
		$json_tmp_base = wp_tempnam( 'plan_clavier_' . sanitize_file_name( $nom ) );
		$json_path     = $json_tmp_base . '.json';
		if ( file_put_contents( $json_path, $plan_json ) !== false ) {
			$attachments[] = $json_path;
		} else {
			$json_path = '';
		}
	}

	/* ── Build all field lines for luthier email ── */
	$field_lines = "Nouvelle inscription reçue :\n\n";
	foreach ( $fields as $key => $val ) {
		if ( ! empty( $val ) ) {
			$field_lines .= sanitize_text_field( $key ) . ' : ' . sanitize_text_field( (string) $val ) . "\n";
		}
	}
	if ( ! empty( $plan_json ) ) {
		$field_lines .= "\n🗃️ Le fichier JSON du plan de clavier personnalisé est joint en pièce jointe.";
	}
	$field_lines .= "\n\nLe récapitulatif PDF est joint.";

	/* ── Email to luthier ── */
	$luthier_subject = 'Nouvelle inscription stage — ' . $nom;
	$safe_luthier    = sanitize_email( $luthier_email );
	$headers_luthier = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: ' . $safe_luthier,
		'Reply-To: ' . $email,
	);

	$luthier_sent = wp_mail(
		$safe_luthier,
		$luthier_subject,
		$field_lines,
		$headers_luthier,
		$attachments
	);

	/* ── Confirmation email to trainee (HTML) ── */
	$replacements = array(
		'{nom}'          => $nom,
		'{modele}'       => $modele,
		'{acompte}'      => $acompte,
		'{session}'      => $session,
		'{bank_details}' => $bank_details,
		'{email}'        => $email,
		'{telephone}'    => $tel,
	);
	$conf_subject_filled = str_replace( array_keys( $replacements ), array_values( $replacements ), $conf_subject );

	/* Replace variables — escape HTML values, bank_details newlines → <br> */
	$html_replacements = array();
	foreach ( $replacements as $key => $val ) {
		if ( $key === '{bank_details}' ) {
			$html_replacements[ $key ] = nl2br( esc_html( $val ) );
		} else {
			$html_replacements[ $key ] = esc_html( $val );
		}
	}
	$conf_body_html = str_replace( array_keys( $html_replacements ), array_values( $html_replacements ), $html_tpl );
	$headers_conf   = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: Ewen Daviau <' . $safe_luthier . '>',
		'Reply-To: ' . $safe_luthier,
	);

	/* Verify temp files still exist before sending trainee email */
	$trainee_attachments = array();
	foreach ( $attachments as $att_path ) {
		if ( file_exists( $att_path ) ) {
			$trainee_attachments[] = $att_path;
		}
	}

	/* Trainee receives the same attachments as the luthier (PDF recap + JSON plan if present) */
	$trainee_sent = wp_mail( $email, $conf_subject_filled, $conf_body_html, $headers_conf, $trainee_attachments );

	if ( ! $trainee_sent ) {
		error_log( '[Stages Lutherie] Failed to send trainee confirmation email — ' . count( $trainee_attachments ) . ' attachment(s)' );
	}

	/* ── Cleanup temp files ── */
	$tmp_files = array( $pdf_path, $pdf_path_pdf, $json_tmp_base, $json_path );
	foreach ( $tmp_files as $tmp ) {
		if ( ! empty( $tmp ) && file_exists( $tmp ) ) {
			unlink( $tmp );
		}
	}

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => 'Inscription envoyée avec succès !',
		),
		200
	);
}

endif; // function_exists stluth_handle_inscription

/* ══════════════════════════════════════════════════════
   OPTIONS MIGRATION  (sl_ → stluth_)
   Copies any settings saved under the old sl_ names so
   that previously-configured values are not lost.
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_migrate_options' ) ) :

add_action( 'admin_init', 'stluth_migrate_options', 1 );

function stluth_migrate_options() {
	$map = array(
		'sl_luthier_email'        => 'stluth_luthier_email',
		'sl_bank_details'         => 'stluth_bank_details',
		'sl_confirmation_subject' => 'stluth_confirmation_subject',
		'sl_confirmation_body'    => 'stluth_confirmation_body',
	);
	foreach ( $map as $old_key => $new_key ) {
		/* Only migrate if the new key has never been saved */
		if ( get_option( $new_key, null ) === null ) {
			$old_val = get_option( $old_key, null );
			if ( $old_val !== null ) {
				update_option( $new_key, $old_val );
			}
		}
	}
}

endif; // function_exists stluth_migrate_options

/* ══════════════════════════════════════════════════════
   ADMIN SETTINGS PAGE
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_add_settings_page' ) ) :

add_action( 'admin_menu', 'stluth_add_settings_page' );

function stluth_add_settings_page() {
	add_options_page(
		'Inscription Stage',
		'Inscription Stage',
		'manage_options',
		'stluth_inscription',
		'stluth_render_settings_page'
	);
}

endif; // function_exists stluth_add_settings_page

if ( ! function_exists( 'stluth_register_settings' ) ) :

add_action( 'admin_init', 'stluth_register_settings' );

function stluth_register_settings() {
	register_setting( 'stluth_inscription', 'stluth_luthier_email',        array( 'sanitize_callback' => 'sanitize_email' ) );
	register_setting( 'stluth_inscription', 'stluth_bank_details',         array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_subject', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_body',    array( 'sanitize_callback' => 'stluth_sanitize_email_html' ) );
}

endif; // function_exists stluth_register_settings

/* Sanitize full HTML email body — wp_kses_post strips html/head/body/meta/title
   which are required for a complete email document. Only admins (manage_options)
   can edit this setting, so we strip dangerous tags and event-handler attributes. */
if ( ! function_exists( 'stluth_sanitize_email_html' ) ) :
function stluth_sanitize_email_html( $value ) {
	/* Remove dangerous tags (with content) */
	$dangerous = 'script|iframe|object|embed|applet|form|input|button';
	$value = preg_replace( '#<(' . $dangerous . ')[\s>][^<]*(?:<(?!/?\1[\s>])[^<]*)*</\1\s*>#i', '', $value );
	/* Remove self-closing dangerous tags */
	$value = preg_replace( '#<(' . $dangerous . ')\s*/?\s*>#i', '', $value );
	/* Remove event handler attributes (on*="...") from all remaining tags */
	$value = preg_replace( '#\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $value );
	/* Remove javascript: protocol in href/src attributes */
	$value = preg_replace( '#(href|src)\s*=\s*(["\'])javascript:.*?\2#i', '$1=$2$2', $value );
	return $value;
}
endif; // function_exists stluth_sanitize_email_html

if ( ! function_exists( 'stluth_render_settings_page' ) ) :

function stluth_render_settings_page() {
	$defaults = array(
		'stluth_luthier_email'        => 'contact@ewendaviau.com',
		'stluth_bank_details'         => "IBAN : FR76 1380 7008 7907 0218 7398 930\nBIC : CCBPFRPPNAN\nTitulaire : Ewen Daviau",
		'stluth_confirmation_subject' => 'Confirmation d\'inscription — Stage de fabrication d\'accordéon',
	);

	/* Valeur courante du corps HTML — si vide, on propose le modèle par défaut */
	$current_body = get_option( 'stluth_confirmation_body', '' );
	$body_for_display = ! empty( $current_body ) ? $current_body : stluth_default_email_html();

	/* Réinitialisation au modèle par défaut */
	if ( isset( $_POST['stluth_reset_body'] ) && check_admin_referer( 'stluth_reset_body_nonce' ) ) {
		update_option( 'stluth_confirmation_body', stluth_default_email_html() );
		$body_for_display = stluth_default_email_html();
		echo '<div class="notice notice-success is-dismissible"><p>✅ Corps de l\'email réinitialisé au modèle par défaut.</p></div>';
	}
	?>
	<div class="wrap">
		<h1>Réglages inscription stage</h1>

		<div class="notice notice-info" style="padding:10px 16px;margin:16px 0 20px 0;">
			<p style="margin:4px 0;">
				<strong>📧 Email de confirmation stagiaire</strong> — tout est modifiable ici, aucun FTP nécessaire.<br>
				<small style="color:#555;">Variables disponibles dans le corps de l'email :
				<code>{nom}</code>, <code>{modele}</code>, <code>{session}</code>,
				<code>{acompte}</code>, <code>{bank_details}</code>,
				<code>{email}</code>, <code>{telephone}</code></small>
			</p>
		</div>

		<form method="post" action="options.php">
			<?php settings_fields( 'stluth_inscription' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Email luthier<br><small style="font-weight:normal;">(destinataire des nouvelles inscriptions)</small></th>
					<td><input type="email" name="stluth_luthier_email" value="<?php echo esc_attr( get_option( 'stluth_luthier_email', $defaults['stluth_luthier_email'] ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row">Coordonnées bancaires</th>
					<td>
						<textarea name="stluth_bank_details" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'stluth_bank_details', $defaults['stluth_bank_details'] ) ); ?></textarea>
						<p class="description">Insérées dans l'email via la variable <code>{bank_details}</code>. Chaque retour à la ligne est conservé.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Objet du mail de confirmation</th>
					<td><input type="text" name="stluth_confirmation_subject" value="<?php echo esc_attr( get_option( 'stluth_confirmation_subject', $defaults['stluth_confirmation_subject'] ) ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th scope="row">Corps du mail de confirmation<br><small style="font-weight:normal;">(HTML complet)</small></th>
					<td>
						<textarea name="stluth_confirmation_body" rows="30" class="large-text code" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $body_for_display ); ?></textarea>
						<p class="description">
							HTML complet de l'email envoyé au stagiaire. Utilisez les variables ci-dessus pour personnaliser.
							<br>Les <strong>coordonnées bancaires</strong> sont injectées automatiquement depuis le champ dédié — modifiez-les dans le champ "Coordonnées bancaires", pas directement ici.
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Enregistrer les réglages' ); ?>
		</form>

		<hr style="margin:32px 0 24px;">
		<h2 style="font-size:1rem;">🔄 Réinitialiser le corps de l'email</h2>
		<p>Remplace le corps de l'email par le <strong>modèle par défaut</strong> (mise en page originale avec les couleurs du site).</p>
		<form method="post">
			<?php wp_nonce_field( 'stluth_reset_body_nonce' ); ?>
			<input type="hidden" name="stluth_reset_body" value="1">
			<?php submit_button( 'Réinitialiser au modèle par défaut', 'secondary', 'submit_reset', false ); ?>
		</form>
	</div>
	<?php
}

endif; // function_exists stluth_render_settings_page
