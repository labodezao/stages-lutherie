<?php
/**
 * Plugin Name: Stages Lutherie — Inscription REST API
 * Description: Endpoint REST pour recevoir les inscriptions du formulaire,
 *              envoyer un email au luthier (avec PDF + JSON joints) et un
 *              email de confirmation au stagiaire (avec PDF joint).
 * Version:     2.3
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

/* Plugin version — displayed on the settings page so the admin can verify
   they are running the latest version after an FTP upload. */
define( 'STLUTH_API_VERSION', '2.4' );

/* ── Log wp_mail failures for debugging ── */
if ( ! has_action( 'wp_mail_failed', 'stluth_log_mail_error' ) ) :
	add_action( 'wp_mail_failed', 'stluth_log_mail_error' );
	function stluth_log_mail_error( $wp_error ) {
		error_log( '[Stages Lutherie] wp_mail FAILED: ' . $wp_error->get_error_message() );
		$data = $wp_error->get_error_data();
		if ( ! empty( $data['to'] ) ) {
			$to = is_array( $data['to'] ) ? implode( ', ', $data['to'] ) : $data['to'];
			error_log( '[Stages Lutherie]   → To: ' . $to );
		}
		if ( ! empty( $data['subject'] ) ) {
			error_log( '[Stages Lutherie]   → Subject: ' . $data['subject'] );
		}
	}
endif;

if ( ! function_exists( 'stluth_send_html_mail' ) ) :
	function stluth_send_html_mail( $to, $subject, $message, $headers = array(), $attachments = array() ) {
		$clean_headers = array();
		$has_content_type = false;
		foreach ( (array) $headers as $header ) {
			if ( stripos( $header, 'Content-Type:' ) === 0 ) {
				$has_content_type = true;
				$clean_headers[]  = 'Content-Type: text/html; charset=UTF-8';
				continue;
			}
			$clean_headers[] = $header;
		}
		if ( ! $has_content_type ) {
			$clean_headers[] = 'Content-Type: text/html; charset=UTF-8';
		}

		return wp_mail( $to, $subject, $message, $clean_headers, $attachments );
	}
endif;

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
            <p style="margin:0;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#3E2723;font-weight:bold;">Demande d\'inscription reçue</p>
          </td>
        </tr>

        <!-- SALUTATION -->
        <tr>
          <td style="padding:36px 40px 20px 40px;">
            <p style="margin:0 0 16px 0;font-size:16px;color:#2c2c2c;line-height:1.6;">Bonjour <strong>{nom}</strong>,</p>
            <p style="margin:0 0 16px 0;font-size:15px;color:#2c2c2c;line-height:1.7;">
              Merci pour votre demande d\'inscription au stage de fabrication d\'accordéon diatonique&nbsp;!
              Votre dossier a bien été reçu et enregistré.
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fffbea;border-left:4px solid #D4A017;border-radius:0 4px 4px 0;margin-bottom:0;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0;font-size:14px;color:#3E2723;line-height:1.7;">
                    ⏳ <strong>Inscription en attente de validation.</strong><br>
                    Votre place sera définitivement réservée dès réception de votre acompte de <strong>{acompte}&nbsp;€</strong>.
                    Un email de confirmation vous sera envoyé à ce moment-là.
                  </p>
                </td>
              </tr>
            </table>
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
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Email</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{email}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Téléphone</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{telephone}</td>
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
              Dès réception de votre virement, votre inscription sera validée et vous recevrez un email de confirmation.
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

</body>
</html>';
}
endif; // function_exists stluth_default_email_html

/* ── English default email template ── */
if ( ! function_exists( 'stluth_default_email_html_en' ) ) :
function stluth_default_email_html_en() {
	return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration confirmation — Diatonic accordion building workshop</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f0eb;font-family:Georgia,\'Times New Roman\',serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f0eb;padding:30px 10px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.12);">

        <!-- HEADER -->
        <tr>
          <td style="background-color:#3E2723;padding:32px 40px;text-align:center;">
            <p style="margin:0 0 4px 0;font-family:Georgia,serif;font-size:10px;letter-spacing:4px;text-transform:uppercase;color:#D4A017;">✦ Lutherie workshop · 2026 ✦</p>
            <h1 style="margin:8px 0 6px 0;font-family:Georgia,serif;font-size:26px;font-weight:normal;color:#ffffff;letter-spacing:1px;">Ewen Daviau</h1>
            <p style="margin:0;font-family:Georgia,serif;font-size:13px;color:#F5D061;font-style:italic;">Diatonic accordion building</p>
          </td>
        </tr>

        <!-- TITLE BANNER -->
        <tr>
          <td style="background-color:#D4A017;padding:12px 40px;text-align:center;">
            <p style="margin:0;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#3E2723;font-weight:bold;">Registration request received</p>
          </td>
        </tr>

        <!-- GREETING -->
        <tr>
          <td style="padding:36px 40px 20px 40px;">
            <p style="margin:0 0 16px 0;font-size:16px;color:#2c2c2c;line-height:1.6;">Hello <strong>{nom}</strong>,</p>
            <p style="margin:0 0 16px 0;font-size:15px;color:#2c2c2c;line-height:1.7;">
              Thank you for your registration request for the diatonic accordion building workshop!
              Your application has been received and registered.
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fffbea;border-left:4px solid #D4A017;border-radius:0 4px 4px 0;margin-bottom:0;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0;font-size:14px;color:#3E2723;line-height:1.7;">
                    ⏳ <strong>Registration pending validation.</strong><br>
                    Your place will be definitively reserved upon receipt of your deposit of <strong>{acompte}&nbsp;€</strong>.
                    A confirmation email will be sent to you at that time.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- SUMMARY -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#faf7f3;border:1px solid #e0d4c4;border-radius:4px;overflow:hidden;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">Summary</p>
                </td>
              </tr>
              <tr>
                <td style="padding:20px;">
                  <table role="presentation" width="100%" cellpadding="6" cellspacing="0">
                    <tr>
                      <td style="width:46%;font-size:13px;color:#7a6a55;font-family:Georgia,serif;vertical-align:top;">Chosen&nbsp;model</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;vertical-align:top;">{modele}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Session</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{session}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Email</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{email}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Phone</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{telephone}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Deposit&nbsp;(40&nbsp;%)</td>
                      <td style="font-size:16px;color:#3E2723;font-weight:bold;padding-top:10px;vertical-align:top;">{acompte}&nbsp;€</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- BANK DETAILS -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fdf8ee;border-left:4px solid #D4A017;border-radius:0 4px 4px 0;">
              <tr>
                <td style="padding:18px 20px;">
                  <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:#7a6a55;font-family:Georgia,serif;">Bank details</p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.8;">{bank_details}</p>
                  <p style="margin:14px 0 0 0;font-size:13px;color:#5a4a35;line-height:1.6;">
                    ⚠️ Please include as transfer reference&nbsp;:
                    <strong style="color:#3E2723;">STAGE-{nom}</strong>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- YOUR ACCORDION -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#f0ece6;border-radius:4px;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;border-radius:4px 4px 0 0;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">🎵 Your accordion</p>
                </td>
              </tr>
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    During these 10 days, you will build your diatonic accordion from A to Z&nbsp;:
                    cutting and assembling the case, mounting the reed block and mechanism,
                    installing and tuning the reeds, making the bellows, and finishing touches.
                    You leave with <strong>your own instrument</strong>.
                  </p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    No woodworking experience is required — just curiosity and motivation&nbsp;!
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ATTACHMENTS -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#fdf8ee;border:1px solid #e0d4c4;border-radius:4px;">
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px 0;font-size:13px;letter-spacing:1px;text-transform:uppercase;color:#7a6a55;font-family:Georgia,serif;">📎 Attachments</p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.8;">
                    📄 <strong>PDF summary</strong> — the full details of your registration<br>
                    🎹 <strong>Keyboard layout</strong> (JSON) — the note layout of your accordion
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- INFO TEXT -->
        <tr>
          <td style="padding:0 40px 32px 40px;">
            <p style="margin:0 0 12px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              Once your bank transfer is received, your registration will be validated and you will receive a confirmation email.
            </p>
            <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              For any questions, feel free to contact me&nbsp;:
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- SIGNATURE -->
        <tr>
          <td style="padding:0 40px 36px 40px;border-top:1px solid #e0d4c4;">
            <p style="margin:24px 0 4px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">See you soon at the workshop&nbsp;!</p>
            <p style="margin:0 0 4px 0;font-size:15px;color:#3E2723;font-family:Georgia,serif;font-weight:bold;">Ewen Daviau</p>
            <p style="margin:0;font-size:13px;color:#7a6a55;line-height:1.7;">
              9 rue Fernand de Magellan — 44600 Saint-Nazaire<br>
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a> —
              <a href="https://ewendaviau.com" style="color:#D4A017;text-decoration:none;">ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0e8dc;padding:16px 40px;text-align:center;border-top:1px solid #e0d4c4;">
            <p style="margin:0;font-size:11px;color:#a09080;line-height:1.6;">
              This email was sent following your registration on
              <a href="https://ewendaviau.com" style="color:#a09080;">ewendaviau.com</a>.
              You can contact the workshop at any time to modify or cancel your registration.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>';
}
endif; // function_exists stluth_default_email_html_en

/* ── French default payment-confirmed email template ── */
if ( ! function_exists( 'stluth_default_payment_confirmed_html' ) ) :
function stluth_default_payment_confirmed_html() {
	return '<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription confirmée — Stage de fabrication d\'accordéon</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f0eb;font-family:Georgia,\'Times New Roman\',serif;">

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
          <td style="background-color:#2e7d32;padding:12px 40px;text-align:center;">
            <p style="margin:0;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#ffffff;font-weight:bold;">✅ Inscription confirmée</p>
          </td>
        </tr>

        <!-- SALUTATION -->
        <tr>
          <td style="padding:36px 40px 20px 40px;">
            <p style="margin:0 0 16px 0;font-size:16px;color:#2c2c2c;line-height:1.6;">Bonjour <strong>{nom}</strong>,</p>
            <p style="margin:0 0 16px 0;font-size:15px;color:#2c2c2c;line-height:1.7;">
              Votre paiement a bien été reçu — merci&nbsp;!
              Votre inscription au stage de fabrication d\'accordéon diatonique est désormais <strong>confirmée et définitive</strong>.
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#e8f5e9;border-left:4px solid #2e7d32;border-radius:0 4px 4px 0;margin-bottom:0;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0;font-size:14px;color:#1b5e20;line-height:1.7;">
                    🎉 <strong>Votre place est réservée&nbsp;!</strong><br>
                    Nous vous attendons avec impatience à l\'atelier pour construire votre accordéon.
                  </p>
                </td>
              </tr>
            </table>
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
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Email</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{email}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Statut</td>
                      <td style="font-size:14px;color:#2e7d32;font-weight:bold;padding-top:10px;vertical-align:top;">✅ Paiement reçu · Inscription confirmée</td>
                    </tr>
                  </table>
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

        <!-- TEXTE INFO -->
        <tr>
          <td style="padding:0 40px 32px 40px;">
            <p style="margin:0 0 12px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              N\'hésitez pas à me contacter pour toute question pratique concernant le stage&nbsp;:
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
              Cet email vous a été envoyé suite à la validation de votre inscription sur
              <a href="https://ewendaviau.com" style="color:#a09080;">ewendaviau.com</a>.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>';
}
endif; // function_exists stluth_default_payment_confirmed_html

/* ── English default payment-confirmed email template ── */
if ( ! function_exists( 'stluth_default_payment_confirmed_html_en' ) ) :
function stluth_default_payment_confirmed_html_en() {
	return '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration confirmed — Diatonic accordion building workshop</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f0eb;font-family:Georgia,\'Times New Roman\',serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f0eb;padding:30px 10px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.12);">

        <!-- HEADER -->
        <tr>
          <td style="background-color:#3E2723;padding:32px 40px;text-align:center;">
            <p style="margin:0 0 4px 0;font-family:Georgia,serif;font-size:10px;letter-spacing:4px;text-transform:uppercase;color:#D4A017;">✦ Lutherie workshop · 2026 ✦</p>
            <h1 style="margin:8px 0 6px 0;font-family:Georgia,serif;font-size:26px;font-weight:normal;color:#ffffff;letter-spacing:1px;">Ewen Daviau</h1>
            <p style="margin:0;font-family:Georgia,serif;font-size:13px;color:#F5D061;font-style:italic;">Diatonic accordion building</p>
          </td>
        </tr>

        <!-- TITLE BANNER -->
        <tr>
          <td style="background-color:#2e7d32;padding:12px 40px;text-align:center;">
            <p style="margin:0;font-family:Georgia,serif;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:#ffffff;font-weight:bold;">✅ Registration confirmed</p>
          </td>
        </tr>

        <!-- GREETING -->
        <tr>
          <td style="padding:36px 40px 20px 40px;">
            <p style="margin:0 0 16px 0;font-size:16px;color:#2c2c2c;line-height:1.6;">Hello <strong>{nom}</strong>,</p>
            <p style="margin:0 0 16px 0;font-size:15px;color:#2c2c2c;line-height:1.7;">
              Your payment has been received — thank you&nbsp;!
              Your registration for the diatonic accordion building workshop is now <strong>confirmed and definitive</strong>.
            </p>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#e8f5e9;border-left:4px solid #2e7d32;border-radius:0 4px 4px 0;margin-bottom:0;">
              <tr>
                <td style="padding:14px 18px;">
                  <p style="margin:0;font-size:14px;color:#1b5e20;line-height:1.7;">
                    🎉 <strong>Your place is reserved&nbsp;!</strong><br>
                    We are looking forward to welcoming you at the workshop to build your accordion.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- SUMMARY -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#faf7f3;border:1px solid #e0d4c4;border-radius:4px;overflow:hidden;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">Summary</p>
                </td>
              </tr>
              <tr>
                <td style="padding:20px;">
                  <table role="presentation" width="100%" cellpadding="6" cellspacing="0">
                    <tr>
                      <td style="width:46%;font-size:13px;color:#7a6a55;font-family:Georgia,serif;vertical-align:top;">Chosen&nbsp;model</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;vertical-align:top;">{modele}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Session</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{session}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Email</td>
                      <td style="font-size:14px;color:#2c2c2c;font-weight:bold;padding-top:10px;vertical-align:top;">{email}</td>
                    </tr>
                    <tr style="border-top:1px solid #e0d4c4;">
                      <td style="font-size:13px;color:#7a6a55;font-family:Georgia,serif;padding-top:10px;vertical-align:top;">Status</td>
                      <td style="font-size:14px;color:#2e7d32;font-weight:bold;padding-top:10px;vertical-align:top;">✅ Payment received · Registration confirmed</td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- YOUR ACCORDION -->
        <tr>
          <td style="padding:0 40px 28px 40px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background-color:#f0ece6;border-radius:4px;">
              <tr>
                <td style="background-color:#3E2723;padding:10px 20px;border-radius:4px 4px 0 0;">
                  <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#D4A017;font-family:Georgia,serif;">🎵 Your accordion</p>
                </td>
              </tr>
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 8px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    During these 10 days, you will build your diatonic accordion from A to Z&nbsp;:
                    cutting and assembling the case, mounting the reed block and mechanism,
                    installing and tuning the reeds, making the bellows, and finishing touches.
                    You leave with <strong>your own instrument</strong>.
                  </p>
                  <p style="margin:0;font-size:14px;color:#2c2c2c;line-height:1.7;">
                    No woodworking experience is required — just curiosity and motivation&nbsp;!
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- INFO TEXT -->
        <tr>
          <td style="padding:0 40px 32px 40px;">
            <p style="margin:0 0 12px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">
              Feel free to contact me for any practical questions about the workshop&nbsp;:
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- SIGNATURE -->
        <tr>
          <td style="padding:0 40px 36px 40px;border-top:1px solid #e0d4c4;">
            <p style="margin:24px 0 4px 0;font-size:14px;color:#2c2c2c;line-height:1.7;">See you soon at the workshop&nbsp;!</p>
            <p style="margin:0 0 4px 0;font-size:15px;color:#3E2723;font-family:Georgia,serif;font-weight:bold;">Ewen Daviau</p>
            <p style="margin:0;font-size:13px;color:#7a6a55;line-height:1.7;">
              9 rue Fernand de Magellan — 44600 Saint-Nazaire<br>
              <a href="mailto:contact@ewendaviau.com" style="color:#D4A017;text-decoration:none;">contact@ewendaviau.com</a> —
              <a href="https://ewendaviau.com" style="color:#D4A017;text-decoration:none;">ewendaviau.com</a>
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background-color:#f0e8dc;padding:16px 40px;text-align:center;border-top:1px solid #e0d4c4;">
            <p style="margin:0;font-size:11px;color:#a09080;line-height:1.6;">
              This email was sent following the validation of your registration on
              <a href="https://ewendaviau.com" style="color:#a09080;">ewendaviau.com</a>.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>';
}
endif; // function_exists stluth_default_payment_confirmed_html_en

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

/* ══════════════════════════════════════════════════════
   AVAILABILITY ENDPOINT — GET /stages-lutherie/v1/availability
   Returns current inscription counts per session and model.
   Used by the inscription forms to grey out full options.
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_count_inscriptions' ) ) :

/**
 * Count non-cancelled inscriptions for a session, optionally filtered by model.
 *
 * @param string $session  Session ID (e.g. 'avril2026').
 * @param string $modele   Model value (e.g. '33/24b'), or '' for session total.
 * @return int
 */
function stluth_count_inscriptions( $session, $modele ) {
	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_stluth_session',
			'value'   => $session,
			'compare' => '=',
		),
	);
	if ( '' !== $modele ) {
		$meta_query[] = array(
			'key'     => '_stluth_modele',
			'value'   => $modele,
			'compare' => '=',
		);
	}
	$q = new WP_Query( array(
		'post_type'      => 'stluth_inscription',
		'post_status'    => array( 'publish', 'stluth_pending', 'stluth_paid' ),
		'meta_query'     => $meta_query,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	return (int) $q->post_count;
}

endif; // function_exists stluth_count_inscriptions

if ( ! function_exists( 'stluth_register_availability_route' ) ) :

add_action( 'rest_api_init', 'stluth_register_availability_route', 99 );

function stluth_register_availability_route() {
	register_rest_route(
		'stages-lutherie/v1',
		'/availability',
		array(
			'methods'             => 'GET',
			'callback'            => 'stluth_handle_availability',
			'permission_callback' => '__return_true',
		)
	);
}

endif; // function_exists stluth_register_availability_route

if ( ! function_exists( 'stluth_handle_availability' ) ) :

function stluth_handle_availability( WP_REST_Request $request ) {
	$all_models = array( '21/8b', '33/12b', '33/18b', '33/24b' );
	$cap_map    = array(
		'21/8b'  => (int) get_option( 'stluth_cap_21_8b',  6 ),
		'33/12b' => (int) get_option( 'stluth_cap_33_12b', 5 ),
		'33/18b' => (int) get_option( 'stluth_cap_33_18b', 3 ),
		'33/24b' => (int) get_option( 'stluth_cap_33_24b', 3 ),
	);
	$cap_total = (int) get_option( 'stluth_cap_total', 15 );

	/* Build session list from ?sessions= param (comma-separated) */
	$sessions_param = sanitize_text_field( $request->get_param( 'sessions' ) );
	$session_ids    = array();
	if ( ! empty( $sessions_param ) ) {
		foreach ( explode( ',', $sessions_param ) as $s ) {
			$s = sanitize_text_field( trim( $s ) );
			if ( ! empty( $s ) ) {
				$session_ids[] = $s;
			}
		}
	}

	/* Also discover sessions already in the database (non-cancelled only, consistent with counting) */
	$pids = get_posts( array(
		'post_type'      => 'stluth_inscription',
		'post_status'    => array( 'publish', 'stluth_pending', 'stluth_paid' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $pids as $pid ) {
		$s = sanitize_text_field( get_post_meta( $pid, '_stluth_session', true ) );
		if ( ! empty( $s ) && ! in_array( $s, $session_ids, true ) ) {
			$session_ids[] = $s;
		}
	}

	$result = array();
	foreach ( $session_ids as $session_id ) {
		$total_used   = stluth_count_inscriptions( $session_id, '' );
		$session_full = $total_used >= $cap_total;
		$all_full     = true;
		$models_data  = array();

		foreach ( $all_models as $model ) {
			$cap       = $cap_map[ $model ];
			$used      = stluth_count_inscriptions( $session_id, $model );
			$remaining = max( 0, $cap - $used );
			$full      = $session_full || ( $used >= $cap );
			if ( ! $full ) {
				$all_full = false;
			}
			$models_data[ $model ] = array(
				'max'       => $cap,
				'used'      => $used,
				'remaining' => $remaining,
				'full'      => $full,
			);
		}

		/* Mark session full when all individual model caps are reached */
		if ( $all_full && ! $session_full ) {
			$session_full = true;
		}

		$result[ $session_id ] = array(
			'max'       => $cap_total,
			'used'      => $total_used,
			'remaining' => max( 0, $cap_total - $total_used ),
			'full'      => $session_full,
			'models'    => $models_data,
		);
	}

	return new WP_REST_Response( array( 'sessions' => $result ), 200 );
}

endif; // function_exists stluth_handle_availability

if ( ! function_exists( 'stluth_handle_inscription' ) ) :
function stluth_handle_inscription( WP_REST_Request $request ) {
	/* Log raw body size — helps diagnose truncation by WAF / post_max_size */
	$raw_body = $request->get_body();
	error_log( '[Stages Lutherie] Raw body size: ' . strlen( $raw_body ) . ' bytes, post_max_size=' . ini_get( 'post_max_size' ) );

	$data        = $request->get_json_params();
	$file_params = $request->get_file_params();
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	$fields = isset( $data['fields'] ) ? (array) $data['fields'] : array();
	if ( empty( $fields ) ) {
		$fields_param = $request->get_param( 'fields' );
		if ( is_array( $fields_param ) ) {
			$fields = $fields_param;
		} elseif ( is_string( $fields_param ) && ! empty( $fields_param ) ) {
			$decoded_fields = json_decode( wp_unslash( $fields_param ), true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				error_log( '[Stages Lutherie] Invalid multipart fields JSON: ' . json_last_error_msg() );
			} elseif ( is_array( $decoded_fields ) ) {
				$fields = $decoded_fields;
			}
		}
	}
	$pdf_base64_param = $request->get_param( 'pdfBase64' );
	$plan_json_param  = $request->get_param( 'planJson' );
	$pdf_base64       = isset( $data['pdfBase64'] ) ? (string) $data['pdfBase64'] : ( is_string( $pdf_base64_param ) ? $pdf_base64_param : '' );
	$plan_json        = isset( $data['planJson'] ) ? (string) $data['planJson'] : ( is_string( $plan_json_param ) ? $plan_json_param : '' );
	$pdf_upload = ( isset( $file_params['pdfFile'] ) && is_array( $file_params['pdfFile'] ) ) ? $file_params['pdfFile'] : array();

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
	$lang     = sanitize_text_field( isset( $fields['lang'] )         ? $fields['lang']         : 'fr' );

	/* ── Capacity check — reject if session or model is full ── */
	if ( function_exists( 'stluth_count_inscriptions' ) && ! empty( $session ) && ! empty( $modele ) ) {
		$cap_total   = (int) get_option( 'stluth_cap_total', 15 );
		$count_total = stluth_count_inscriptions( $session, '' );
		if ( $count_total >= $cap_total ) {
			return new WP_REST_Response(
				array( 'success' => false, 'message' => 'La session est complète. Aucune place disponible.' ),
				409
			);
		}
		$model_cap_keys = array(
			'21/8b'  => array( 'stluth_cap_21_8b',  6 ),
			'33/12b' => array( 'stluth_cap_33_12b', 5 ),
			'33/18b' => array( 'stluth_cap_33_18b', 3 ),
			'33/24b' => array( 'stluth_cap_33_24b', 3 ),
		);
		if ( isset( $model_cap_keys[ $modele ] ) ) {
			list( $cap_opt, $cap_default ) = $model_cap_keys[ $modele ];
			$cap_model   = (int) get_option( $cap_opt, $cap_default );
			$count_model = stluth_count_inscriptions( $session, $modele );
			if ( $count_model >= $cap_model ) {
				return new WP_REST_Response(
					array( 'success' => false, 'message' => sprintf( 'Le modèle "%s" est complet pour cette session.', $modele ) ),
					409
				);
			}
		}
	}

	/* ── WordPress option defaults ── */
	$defaults = array(
		'stluth_luthier_email'        => 'contact@ewendaviau.com',
		'stluth_luthier_name'         => 'Ewen Daviau',
		'stluth_bank_details'         => "IBAN : FR76 1380 7008 7907 0218 7398 930\nBIC : CCBPFRPPNAN\nTitulaire : Ewen Daviau",
		'stluth_confirmation_subject' => 'Confirmation d\'inscription — Stage de fabrication d\'accordéon',
		'stluth_luthier_subject'      => 'Nouvelle inscription stage — {nom}',
		'stluth_luthier_body'         => "Nouvelle inscription reçue :\n\nNom : {nom}\nEmail : {email}\nTéléphone : {telephone}\nModèle : {modele}\nSession : {session}\nAcompte : {acompte} €\n\nLe récapitulatif PDF et le plan de clavier JSON sont joints.",
	);

	$luthier_email = get_option( 'stluth_luthier_email', $defaults['stluth_luthier_email'] );
	$luthier_name  = get_option( 'stluth_luthier_name',  $defaults['stluth_luthier_name']  );
	$bank_details  = get_option( 'stluth_bank_details',  $defaults['stluth_bank_details']  );

	if ( $lang === 'en' ) {
		$conf_subject = get_option( 'stluth_confirmation_subject_en', 'Registration confirmation — Diatonic accordion building workshop' );
		$stored_body  = get_option( 'stluth_confirmation_body_en', '' );
		if ( ! empty( $stored_body ) ) {
			$html_tpl = $stored_body;
		} else {
			$html_tpl = function_exists( 'stluth_default_email_html_en' ) ? stluth_default_email_html_en() : stluth_default_email_html();
		}
	} else {
		$conf_subject = get_option( 'stluth_confirmation_subject', $defaults['stluth_confirmation_subject'] );
		/* HTML body : WP option (primary) → FTP file (legacy fallback) → built-in default */
		$stored_body = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored_body ) ) {
			$html_tpl = $stored_body;
		} elseif ( file_exists( __DIR__ . '/email-confirmation-stagiaire.html' ) ) {
			$html_tpl = file_get_contents( __DIR__ . '/email-confirmation-stagiaire.html' );
		} else {
			$html_tpl = stluth_default_email_html();
		}
	}

	/* Runtime corruption check: if the loaded template has no inline styles,
	   it was corrupted by wp_kses or similar processing.  Fall back to the
	   clean built-in default so the trainee receives a properly styled email. */
	if ( stripos( $html_tpl, 'style=' ) === false ) {
		error_log( '[Stages Lutherie] WARNING: email template has no style= attributes (corrupted). Using built-in default.' );
		$html_tpl = ( $lang === 'en' && function_exists( 'stluth_default_email_html_en' ) )
			? stluth_default_email_html_en()
			: stluth_default_email_html();
	}

	/* ── Decode PDF to temp file ── */
	$attachments  = array();
	$pdf_path     = '';
	$pdf_path_pdf = '';
	$pdf_tmp_key  = 'inscription_' . sanitize_file_name( $nom );

	error_log( '[Stages Lutherie] Received inscription: lang=' . $lang . ', pdfBase64 length=' . strlen( $pdf_base64 ) . ', pdf upload=' . ( ! empty( $pdf_upload['tmp_name'] ) ? 'yes' : 'no' ) . ', planJson length=' . strlen( $plan_json ) );

	if ( ! empty( $pdf_upload['tmp_name'] ) && isset( $pdf_upload['error'] ) && UPLOAD_ERR_OK === (int) $pdf_upload['error'] && file_exists( $pdf_upload['tmp_name'] ) ) {
		$pdf_path     = wp_tempnam( $pdf_tmp_key );
		$pdf_path_pdf = $pdf_path . '.pdf';
		if ( copy( $pdf_upload['tmp_name'], $pdf_path_pdf ) ) {
			$attachments[] = $pdf_path_pdf;
			error_log( '[Stages Lutherie] Uploaded PDF copied to ' . $pdf_path_pdf . ' (' . filesize( $pdf_path_pdf ) . ' bytes)' );
		} else {
			error_log( '[Stages Lutherie] ERROR: failed to copy uploaded PDF from ' . $pdf_upload['tmp_name'] . ' to ' . $pdf_path_pdf );
		}
	} elseif ( ! empty( $pdf_base64 ) ) {
		/* Strip data-URI prefix if the frontend accidentally sent the full URI */
		if ( strpos( $pdf_base64, 'data:' ) === 0 ) {
			$comma = strpos( $pdf_base64, ',' );
			if ( false !== $comma ) {
				$pdf_base64 = substr( $pdf_base64, $comma + 1 );
			}
			error_log( '[Stages Lutherie] Stripped data-URI prefix from pdfBase64 (length now ' . strlen( $pdf_base64 ) . ')' );
		}

		/* Remove whitespace / newlines that some encoders or proxies inject —
		   strict base64_decode rejects ANY character outside [A-Za-z0-9+/=]. */
		$pdf_base64 = preg_replace( '/\s+/', '', $pdf_base64 );

		$pdf_data = base64_decode( $pdf_base64, false );

		if ( false === $pdf_data || 0 === strlen( $pdf_data ) ) {
			error_log( '[Stages Lutherie] ERROR: base64_decode failed. First 80 chars: ' . substr( $pdf_base64, 0, 80 ) );
		} else {
			error_log( '[Stages Lutherie] PDF decoded OK: ' . strlen( $pdf_data ) . ' bytes' );
			$pdf_path = wp_tempnam( $pdf_tmp_key );
			// Ensure the file ends with .pdf so mail clients recognise the attachment.
			$pdf_path_pdf = $pdf_path . '.pdf';
			$written = file_put_contents( $pdf_path_pdf, $pdf_data );
			if ( false !== $written && $written > 0 ) {
				$attachments[] = $pdf_path_pdf;
				error_log( '[Stages Lutherie] PDF written to ' . $pdf_path_pdf . ' (' . $written . ' bytes)' );
			} else {
				error_log( '[Stages Lutherie] ERROR: file_put_contents failed for ' . $pdf_path_pdf );
			}
		}
	} else {
		error_log( '[Stages Lutherie] WARNING: no PDF received — neither pdfFile nor pdfBase64 was present. Check frontend submission mode and PHP post_max_size (current: ' . ini_get( 'post_max_size' ) . ').' );
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

	error_log( '[Stages Lutherie] Attachments built: ' . count( $attachments ) . ' file(s) — ' . implode( ', ', array_map( 'basename', $attachments ) ) );

	$perm_pdf  = '';
	$perm_json = '';

	/* ── Persist registration in database ── */
	if ( function_exists( 'stluth_save_registration' ) ) {
		$reg_id = stluth_save_registration( $fields, $pdf_path_pdf, $plan_json );
		if ( is_wp_error( $reg_id ) ) {
			error_log( '[Stages Lutherie] Failed to save registration: ' . $reg_id->get_error_message() );
		} else {
			error_log( '[Stages Lutherie] Registration saved as post #' . $reg_id );
			/* If the PDF was permanently saved, use that copy for attachments
			   so the email attachment survives any temp-file cleanup race. */
			$perm_pdf = get_post_meta( $reg_id, '_stluth_pdf_path', true );
			if ( ! empty( $perm_pdf ) && file_exists( $perm_pdf ) ) {
				$idx = array_search( $pdf_path_pdf, $attachments, true );
				if ( false !== $idx ) {
					$attachments[ $idx ] = $perm_pdf;
				}
				error_log( '[Stages Lutherie] Using permanent PDF for attachments: ' . $perm_pdf );
			}
			/* Same for JSON — prefer the permanent copy over the temp file */
			$perm_json = get_post_meta( $reg_id, '_stluth_json_path', true );
			if ( ! empty( $perm_json ) && file_exists( $perm_json ) && ! empty( $json_path ) ) {
				$idx_j = array_search( $json_path, $attachments, true );
				if ( false !== $idx_j ) {
					$attachments[ $idx_j ] = $perm_json;
				}
				error_log( '[Stages Lutherie] Using permanent JSON for attachments: ' . $perm_json );
			}
		}
	}

	/* ── Variable replacements (used in both emails) ── */
	$replacements = array(
		'{nom}'          => $nom,
		'{email}'        => $email,
		'{telephone}'    => $tel,
		'{modele}'       => $modele,
		'{session}'      => $session,
		'{acompte}'      => $acompte,
		'{bank_details}' => $bank_details,
	);

	/* ── Email to luthier ── */
	$luthier_subj_tpl = get_option( 'stluth_luthier_subject', $defaults['stluth_luthier_subject'] );
	$luthier_body_tpl = get_option( 'stluth_luthier_body',    $defaults['stluth_luthier_body'] );
	$luthier_subject  = str_replace( array_keys( $replacements ), array_values( $replacements ), $luthier_subj_tpl );
	$luthier_body     = str_replace( array_keys( $replacements ), array_values( $replacements ), $luthier_body_tpl );

	$safe_luthier    = sanitize_email( $luthier_email );
	$safe_name       = sanitize_text_field( $luthier_name );
	$headers_luthier = array(
		'Content-Type: text/plain; charset=UTF-8',
		'From: ' . $safe_name . ' <' . $safe_luthier . '>',
		'Reply-To: ' . $email,
	);

	$luthier_sent = wp_mail(
		$safe_luthier,
		$luthier_subject,
		$luthier_body,
		$headers_luthier,
		$attachments
	);

	error_log( '[Stages Lutherie] Luthier email ' . ( $luthier_sent ? 'sent' : 'FAILED' ) . ' to ' . $safe_luthier );

	/* ── Confirmation email to trainee (HTML) ── */

	/* Resolve the PDF path — prefer the permanent copy already saved to disk */
	$trainee_pdf_path = '';
	if ( ! empty( $perm_pdf ) && file_exists( $perm_pdf ) ) {
		$trainee_pdf_path = $perm_pdf;
	} elseif ( ! empty( $pdf_path_pdf ) && file_exists( $pdf_path_pdf ) ) {
		$trainee_pdf_path = $pdf_path_pdf;
	}

	if ( ! empty( $trainee_pdf_path ) ) {
		error_log( '[Stages Lutherie] Trainee PDF for attachment: ' . $trainee_pdf_path . ' (' . filesize( $trainee_pdf_path ) . ' bytes)' );
	} else {
		error_log( '[Stages Lutherie] WARNING: no PDF found for trainee attachment' );
	}

	/* Build attachments array — same pattern as the test email which works */
	$trainee_pdf_attachments = ! empty( $trainee_pdf_path ) ? array( $trainee_pdf_path ) : array();

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

	/* Strip MSO conditional comments so they don't render as visible text
	   in Gmail, Apple Mail, etc. */
	if ( function_exists( 'stluth_strip_mso_conditionals' ) ) {
		$conf_body_html = stluth_strip_mso_conditionals( $conf_body_html );
	}

	$headers_conf = array(
		'From: ' . $safe_name . ' <' . $safe_luthier . '>',
		'Reply-To: ' . $safe_luthier,
	);

	error_log( '[Stages Lutherie] Sending trainee email to ' . $email . ( ! empty( $trainee_pdf_path ) ? ' with PDF: ' . basename( $trainee_pdf_path ) : ' (no PDF)' ) );

	/* Pass PDF as 5th argument — identical to the test HTML email that is known
	   to work.  The previous phpmailer_init hook approach was unreliable because
	   MailPoet MSS (and similar plugins) read wp_mail()'s $attachments parameter
	   before phpmailer_init fires, so attachments added only via that hook were
	   silently dropped. */
	$trainee_sent = stluth_send_html_mail( $email, $conf_subject_filled, $conf_body_html, $headers_conf, $trainee_pdf_attachments );

	if ( $trainee_sent ) {
		error_log( '[Stages Lutherie] Trainee confirmation email sent successfully to ' . $email );
	} else {
		error_log( '[Stages Lutherie] FAILED to send trainee confirmation email to ' . $email );
	}

	/* ── Cleanup temp files ── */
	/* $trainee_pdf_path points to the permanent uploads file (never delete) or
	   to $pdf_path_pdf (the original temp, already listed below). */
	$tmp_files = array( $pdf_path, $pdf_path_pdf, $json_tmp_base, $json_path );
	register_shutdown_function( function () use ( $tmp_files ) {
		foreach ( $tmp_files as $tmp ) {
			if ( ! empty( $tmp ) && file_exists( $tmp ) ) {
				@unlink( $tmp );
			}
		}
	} );

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
   VERSION MIGRATION — runs once per version upgrade.
   Cleans up stored data that was saved by older versions
   before certain sanitizers existed.
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_version_migration' ) ) :

add_action( 'admin_init', 'stluth_version_migration', 2 );

function stluth_version_migration() {
	$db_version = get_option( 'stluth_api_version', '0' );
	if ( version_compare( $db_version, STLUTH_API_VERSION, '>=' ) ) {
		return; /* already up to date */
	}

	/* v1.8 — Re-sanitize stored HTML email body to strip MSO conditionals
	   that were saved before stluth_sanitize_email_html included MSO stripping.
	   This is the root cause of <!--[if mso]> appearing in trainee emails. */
	if ( version_compare( $db_version, '1.8', '<' ) ) {
		$stored = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored ) && function_exists( 'stluth_strip_mso_conditionals' ) ) {
			$cleaned = stluth_strip_mso_conditionals( $stored );
			if ( $cleaned !== $stored ) {
				update_option( 'stluth_confirmation_body', $cleaned );
				error_log( '[Stages Lutherie] v1.8 migration: stripped MSO conditionals from stored email template.' );
			}
		}
	}

	/* v1.9 — Detect and fix corrupted email templates.
	   WordPress or a security plugin may have run wp_kses on the stored HTML,
	   stripping all style="" attributes and HTML-encoding MSO comments as
	   &lt;!--[if mso]&gt;.  If the stored template contains NO style= attrs,
	   it is corrupted beyond repair → replace with the clean default. */
	if ( version_compare( $db_version, '1.9', '<' ) ) {
		$stored = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored ) ) {
			/* Strip entity-encoded MSO first (the v1.8 regex only matched raw MSO) */
			if ( function_exists( 'stluth_strip_mso_conditionals' ) ) {
				$stored = stluth_strip_mso_conditionals( $stored );
			}

			/* If the template has no inline styles, it was corrupted by wp_kses
			   or similar processing.  Replace with the clean built-in default. */
			if ( stripos( $stored, 'style=' ) === false ) {
				$default = function_exists( 'stluth_default_email_html' )
					? stluth_default_email_html()
					: '';
				if ( ! empty( $default ) ) {
					update_option( 'stluth_confirmation_body', $default );
					error_log( '[Stages Lutherie] v1.9 migration: stored email template was corrupted (no style= attributes). Replaced with default.' );
				}
			} else {
				/* Template has styles but may have had entity-encoded MSO — save cleaned version */
				$current = get_option( 'stluth_confirmation_body', '' );
				if ( $stored !== $current ) {
					update_option( 'stluth_confirmation_body', $stored );
					error_log( '[Stages Lutherie] v1.9 migration: stripped entity-encoded MSO conditionals from stored template.' );
				}
			}
		}
	}

	/* v2.0 — Force-reset corrupted French template (belt-and-suspenders for v1.9)
	   and initialize English email template with built-in default. */
	if ( version_compare( $db_version, '2.0', '<' ) ) {
		/* French: force-reset if corrupted */
		$stored_fr = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored_fr ) && stripos( $stored_fr, 'style=' ) === false ) {
			if ( function_exists( 'stluth_default_email_html' ) ) {
				update_option( 'stluth_confirmation_body', stluth_default_email_html() );
				error_log( '[Stages Lutherie] v2.0 migration: French email template corrupted — replaced with default.' );
			}
		}
		/* English: initialize with default if not yet set */
		$stored_en = get_option( 'stluth_confirmation_body_en', '' );
		if ( empty( $stored_en ) && function_exists( 'stluth_default_email_html_en' ) ) {
			update_option( 'stluth_confirmation_body_en', stluth_default_email_html_en() );
			error_log( '[Stages Lutherie] v2.0 migration: initialized English email template with default.' );
		}
	}

	/* v2.1 — Add {email} and {telephone} fields to both email templates.
	   If the stored template doesn't contain the {email} placeholder, refresh
	   it with the updated default that now includes email + phone rows. */
	if ( version_compare( $db_version, '2.1', '<' ) ) {
		/* French template */
		$stored_fr = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored_fr ) && strpos( $stored_fr, '{email}' ) === false ) {
			if ( function_exists( 'stluth_default_email_html' ) ) {
				update_option( 'stluth_confirmation_body', stluth_default_email_html() );
				error_log( '[Stages Lutherie] v2.1 migration: updated French email template to include {email} and {telephone}.' );
			}
		}
		/* English template */
		$stored_en = get_option( 'stluth_confirmation_body_en', '' );
		if ( ! empty( $stored_en ) && strpos( $stored_en, '{email}' ) === false ) {
			if ( function_exists( 'stluth_default_email_html_en' ) ) {
				update_option( 'stluth_confirmation_body_en', stluth_default_email_html_en() );
				error_log( '[Stages Lutherie] v2.1 migration: updated English email template to include {email} and {telephone}.' );
			}
		}
	}

	/* v2.2 — Force-reset any template that still has no style= attributes
	   (corrupted by wp_kses or a security plugin after the v2.0/v2.1 migration).
	   Runs whenever the live site upgrades from ≤2.1 to 2.2. */
	if ( version_compare( $db_version, '2.2', '<' ) ) {
		$stored_fr = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored_fr ) && stripos( $stored_fr, 'style=' ) === false ) {
			if ( function_exists( 'stluth_default_email_html' ) ) {
				update_option( 'stluth_confirmation_body', stluth_default_email_html() );
				error_log( '[Stages Lutherie] v2.2 migration: French email template was corrupted (no style=) — replaced with default.' );
			}
		}
		$stored_en = get_option( 'stluth_confirmation_body_en', '' );
		if ( ! empty( $stored_en ) && stripos( $stored_en, 'style=' ) === false ) {
			if ( function_exists( 'stluth_default_email_html_en' ) ) {
				update_option( 'stluth_confirmation_body_en', stluth_default_email_html_en() );
				error_log( '[Stages Lutherie] v2.2 migration: English email template was corrupted (no style=) — replaced with default.' );
			}
		}
	}

	/* v2.4 — Update registration emails to reflect "received, pending payment validation" messaging.
	   Force-reset both FR and EN confirmation templates if they still contain the old banner title
	   so the new pending-payment wording applies.
	   Also initialize the new payment-confirmed email templates. */
	if ( version_compare( $db_version, '2.4', '<' ) ) {
		/* FR: reset only if banner still has old title */
		$stored_fr = get_option( 'stluth_confirmation_body', '' );
		if ( ! empty( $stored_fr ) && (
			strpos( $stored_fr, 'Confirmation d\'inscription' ) !== false ||
			strpos( $stored_fr, 'Confirmation d&#039;inscription' ) !== false
		) ) {
			if ( function_exists( 'stluth_default_email_html' ) ) {
				update_option( 'stluth_confirmation_body', stluth_default_email_html() );
				error_log( '[Stages Lutherie] v2.4 migration: updated French confirmation email to pending-payment messaging.' );
			}
		}
		/* EN: reset only if banner still has old title */
		$stored_en = get_option( 'stluth_confirmation_body_en', '' );
		if ( ! empty( $stored_en ) && strpos( $stored_en, 'Registration confirmation' ) !== false ) {
			if ( function_exists( 'stluth_default_email_html_en' ) ) {
				update_option( 'stluth_confirmation_body_en', stluth_default_email_html_en() );
				error_log( '[Stages Lutherie] v2.4 migration: updated English confirmation email to pending-payment messaging.' );
			}
		}
		/* Initialize payment-confirmed body templates if not yet set */
		$stored_pc = get_option( 'stluth_payment_confirmed_body', '' );
		if ( empty( $stored_pc ) && function_exists( 'stluth_default_payment_confirmed_html' ) ) {
			update_option( 'stluth_payment_confirmed_body', stluth_default_payment_confirmed_html() );
			error_log( '[Stages Lutherie] v2.4 migration: initialized French payment-confirmed email template.' );
		}
		/* Initialize FR subject only if not already set */
		if ( get_option( 'stluth_payment_confirmed_subject', null ) === null ) {
			update_option( 'stluth_payment_confirmed_subject', 'Votre inscription est confirmée — Stage de fabrication d\'accordéon' );
		}
		$stored_pc_en = get_option( 'stluth_payment_confirmed_body_en', '' );
		if ( empty( $stored_pc_en ) && function_exists( 'stluth_default_payment_confirmed_html_en' ) ) {
			update_option( 'stluth_payment_confirmed_body_en', stluth_default_payment_confirmed_html_en() );
			error_log( '[Stages Lutherie] v2.4 migration: initialized English payment-confirmed email template.' );
		}
		/* Initialize EN subject only if not already set */
		if ( get_option( 'stluth_payment_confirmed_subject_en', null ) === null ) {
			update_option( 'stluth_payment_confirmed_subject_en', 'Your registration is confirmed — Diatonic accordion building workshop' );
		}
	}

	update_option( 'stluth_api_version', STLUTH_API_VERSION );
	error_log( '[Stages Lutherie] Migrated to v' . STLUTH_API_VERSION );
}

endif; // function_exists stluth_version_migration

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
	register_setting( 'stluth_inscription', 'stluth_luthier_name',         array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_bank_details',         array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_subject', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_body',    array( 'sanitize_callback' => 'stluth_sanitize_email_html' ) );
	register_setting( 'stluth_inscription', 'stluth_luthier_subject',      array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_luthier_body',         array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_subject_en', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_confirmation_body_en',    array( 'sanitize_callback' => 'stluth_sanitize_email_html' ) );
	register_setting( 'stluth_inscription', 'stluth_cap_total',   array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'stluth_inscription', 'stluth_cap_21_8b',  array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'stluth_inscription', 'stluth_cap_33_12b', array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'stluth_inscription', 'stluth_cap_33_18b', array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'stluth_inscription', 'stluth_cap_33_24b', array( 'sanitize_callback' => 'absint' ) );
	register_setting( 'stluth_inscription', 'stluth_payment_confirmed_subject',    array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_payment_confirmed_body',       array( 'sanitize_callback' => 'stluth_sanitize_email_html' ) );
	register_setting( 'stluth_inscription', 'stluth_payment_confirmed_subject_en', array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'stluth_inscription', 'stluth_payment_confirmed_body_en',    array( 'sanitize_callback' => 'stluth_sanitize_email_html' ) );
}

endif; // function_exists stluth_register_settings

/**
 * Strip all MSO / IE conditional comment blocks from HTML.
 *
 * Handles the three common patterns in both raw AND HTML-entity-encoded forms:
 *   1) <!--[if mso]>…<![endif]-->           (Outlook-only content → removed entirely)
 *   2) <!--[if !mso]><!--> … <!--<![endif]--> (non-Outlook content → keep inner HTML)
 *   3) <!--[if gte mso 9]>…<![endif]-->     (version variants → removed entirely)
 *
 * The entity-encoded forms (&lt;!--[if mso]&gt; etc.) appear when WordPress
 * or a security plugin runs the HTML through wp_kses / esc_html before storage.
 * They render as visible text in ALL email clients and must be removed.
 */
if ( ! function_exists( 'stluth_strip_mso_conditionals' ) ) :
function stluth_strip_mso_conditionals( $html ) {
	/* ── Raw patterns (proper HTML comments) ── */
	/* Pattern 2 first: <!--[if !mso]><!--> KEEP THIS <!--<![endif]--> */
	$html = preg_replace( '#<!--\[if\s+!mso\]><!-->\s*(.*?)\s*<!--<!\[endif\]-->#si', '$1', $html );
	/* Pattern 1 & 3: <!--[if (anything)]>…<![endif]--> → remove entirely */
	$html = preg_replace( '#<!--\[if\s[^\]]*\]>.*?<!\[endif\]-->#si', '', $html );
	/* Stray conditional leftovers (malformed) */
	$html = preg_replace( '#<!--\[if\s[^\]]*\]>#i', '', $html );
	$html = preg_replace( '#<!\[endif\]-->#i', '', $html );

	/* ── Entity-encoded patterns (&lt;!-- instead of <!--) ── */
	/* These appear when WordPress encodes the HTML before storage.
	   &lt;!--[if mso]&gt; ... &lt;![endif]--&gt;  → remove entirely */
	$html = preg_replace( '#&lt;!--\[if\s+!mso\]&gt;&lt;!--&gt;\s*(.*?)\s*&lt;!--&lt;!\[endif\]--&gt;#si', '$1', $html );
	$html = preg_replace( '#&lt;!--\[if\s[^\]]*\]&gt;.*?&lt;!\[endif\]--&gt;#si', '', $html );
	/* Stray entity-encoded leftovers */
	$html = preg_replace( '#&lt;!--\[if\s[^\]]*\]&gt;#i', '', $html );
	$html = preg_replace( '#&lt;!\[endif\]--&gt;#i', '', $html );

	return $html;
}
endif; // function_exists stluth_strip_mso_conditionals

/* Sanitize full HTML email body — wp_kses_post strips html/head/body/meta/title
   which are required for a complete email document. Only admins (manage_options)
   can edit this setting, so we strip dangerous tags and event-handler attributes. */
if ( ! function_exists( 'stluth_sanitize_email_html' ) ) :
function stluth_sanitize_email_html( $value ) {
	/* Strip MSO conditional comments — they render as visible text in
	   most email clients (Gmail, Apple Mail, etc.) and only Outlook
	   interprets them.  Covers <!--[if mso]>…<![endif]-->,
	   <!--[if !mso]>…<![endif]-->, and similar variants. */
	$value = stluth_strip_mso_conditionals( $value );
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
		'stluth_luthier_name'         => 'Ewen Daviau',
		'stluth_bank_details'         => "IBAN : FR76 1380 7008 7907 0218 7398 930\nBIC : CCBPFRPPNAN\nTitulaire : Ewen Daviau",
		'stluth_confirmation_subject' => 'Confirmation d\'inscription — Stage de fabrication d\'accordéon',
		'stluth_luthier_subject'      => 'Nouvelle inscription stage — {nom}',
		'stluth_luthier_body'         => "Nouvelle inscription reçue :\n\nNom : {nom}\nEmail : {email}\nTéléphone : {telephone}\nModèle : {modele}\nSession : {session}\nAcompte : {acompte} €\n\nLe récapitulatif PDF et le plan de clavier JSON sont joints.",
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

	/* English template reset */
	if ( isset( $_POST['stluth_reset_body_en'] ) && check_admin_referer( 'stluth_reset_body_en_nonce' ) ) {
		$default_en = function_exists( 'stluth_default_email_html_en' ) ? stluth_default_email_html_en() : '';
		update_option( 'stluth_confirmation_body_en', $default_en );
		echo '<div class="notice notice-success is-dismissible"><p>✅ English email body reset to default template.</p></div>';
	}

	/* Payment confirmed FR template reset */
	if ( isset( $_POST['stluth_reset_payment_confirmed'] ) && check_admin_referer( 'stluth_reset_payment_confirmed_nonce' ) ) {
		$default_pc = function_exists( 'stluth_default_payment_confirmed_html' ) ? stluth_default_payment_confirmed_html() : '';
		update_option( 'stluth_payment_confirmed_body', $default_pc );
		echo '<div class="notice notice-success is-dismissible"><p>✅ Corps de l\'email de confirmation de paiement réinitialisé au modèle par défaut.</p></div>';
	}

	/* Payment confirmed EN template reset */
	if ( isset( $_POST['stluth_reset_payment_confirmed_en'] ) && check_admin_referer( 'stluth_reset_payment_confirmed_en_nonce' ) ) {
		$default_pc_en = function_exists( 'stluth_default_payment_confirmed_html_en' ) ? stluth_default_payment_confirmed_html_en() : '';
		update_option( 'stluth_payment_confirmed_body_en', $default_pc_en );
		echo '<div class="notice notice-success is-dismissible"><p>✅ English payment confirmation email body reset to default template.</p></div>';
	}

	/* ── Send test email ── */
	if ( isset( $_POST['stluth_send_test'] ) && check_admin_referer( 'stluth_test_email_nonce' ) ) {
		$test_to = sanitize_email( get_option( 'stluth_luthier_email', $defaults['stluth_luthier_email'] ) );
		$test_nom = 'Test Stagiaire';

		/* Build a small test PDF (just a text file with .pdf extension for testing) */
		$test_pdf_path = wp_tempnam( 'test_inscription.pdf' ) . '.pdf';
		file_put_contents( $test_pdf_path, '%PDF-1.4 test — ce fichier confirme que les pièces jointes fonctionnent.' );

		$test_attachments = array();
		if ( file_exists( $test_pdf_path ) ) {
			$test_attachments[] = $test_pdf_path;
		}

		/* Prepare HTML body from the stored/default template */
		$test_replacements = array(
			'{nom}'          => $test_nom,
			'{email}'        => $test_to,
			'{telephone}'    => '00 00 00 00 00',
			'{modele}'       => 'GC 2 rangs — Test',
			'{session}'      => 'Session test',
			'{acompte}'      => '1 200',
			'{bank_details}' => nl2br( esc_html( get_option( 'stluth_bank_details', $defaults['stluth_bank_details'] ) ) ),
		);
		$test_html = $body_for_display;
		foreach ( $test_replacements as $k => $v ) {
			$test_html = str_replace( $k, $v, $test_html );
		}
		if ( function_exists( 'stluth_strip_mso_conditionals' ) ) {
			$test_html = stluth_strip_mso_conditionals( $test_html );
		}

		$luthier_name = sanitize_text_field( get_option( 'stluth_luthier_name', $defaults['stluth_luthier_name'] ) );

		/* Send HTML test email (like trainee) */
		$test_headers_html = array(
			'From: ' . $luthier_name . ' <' . $test_to . '>',
		);

		$html_sent = stluth_send_html_mail( $test_to, '[TEST HTML] Confirmation inscription — v' . STLUTH_API_VERSION, $test_html, $test_headers_html, $test_attachments );

		/* Send plain text test email (like luthier) */
		$test_headers_plain = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . $luthier_name . ' <' . $test_to . '>',
		);
		$plain_body = "TEST inscription-api.php v" . STLUTH_API_VERSION . "\n\n"
			. "Si vous recevez ce mail avec la pièce jointe PDF,\n"
			. "le mail luthier (texte brut) fonctionne correctement.\n\n"
			. "Nom : " . $test_nom . "\nModèle : GC 2 rangs — Test\n";

		$plain_sent = wp_mail( $test_to, '[TEST PLAIN] Nouvelle inscription — v' . STLUTH_API_VERSION, $plain_body, $test_headers_plain, $test_attachments );

		/* Send English HTML test email */
		$test_html_en = function_exists( 'stluth_default_email_html_en' ) ? stluth_default_email_html_en() : $test_html;
		foreach ( $test_replacements as $k => $v ) {
			$test_html_en = str_replace( $k, $v, $test_html_en );
		}
		if ( function_exists( 'stluth_strip_mso_conditionals' ) ) {
			$test_html_en = stluth_strip_mso_conditionals( $test_html_en );
		}
		$en_sent = stluth_send_html_mail( $test_to, '[TEST EN] Registration confirmation — v' . STLUTH_API_VERSION, $test_html_en, $test_headers_html, $test_attachments );

		/* Cleanup */
		@unlink( $test_pdf_path );

		if ( $html_sent && $plain_sent && $en_sent ) {
			echo '<div class="notice notice-success is-dismissible"><p>✅ <strong>3 emails de test envoyés à ' . esc_html( $test_to ) . '</strong><br>'
				. '📧 [TEST HTML] = simule le mail stagiaire FR (HTML + pièce jointe)<br>'
				. '📩 [TEST PLAIN] = simule le mail luthier (texte brut + pièce jointe)<br>'
				. '🇬🇧 [TEST EN] = simule le mail stagiaire EN (HTML + pièce jointe)<br>'
				. 'Vérifiez votre boîte de réception et comparez les trois !</p></div>';
		} else {
			$msg = '';
			if ( ! $html_sent ) { $msg .= '❌ Email HTML FR (stagiaire) a ÉCHOUÉ. '; }
			if ( ! $plain_sent ) { $msg .= '❌ Email texte (luthier) a ÉCHOUÉ. '; }
			if ( ! $en_sent ) { $msg .= '❌ Email HTML EN (stagiaire) a ÉCHOUÉ. '; }
			echo '<div class="notice notice-error is-dismissible"><p>' . $msg . 'Consultez le debug.log WordPress pour plus de détails.</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1>Réglages inscription stage <small style="font-size:12px;color:#888;font-weight:normal;">— inscription-api.php v<?php echo esc_html( STLUTH_API_VERSION ); ?></small></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'stluth_inscription' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">Nom du luthier<br><small style="font-weight:normal;">(affiché dans « De : » des emails)</small></th>
					<td><input type="text" name="stluth_luthier_name" value="<?php echo esc_attr( get_option( 'stluth_luthier_name', $defaults['stluth_luthier_name'] ) ); ?>" class="regular-text"></td>
				</tr>
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
			</table>

			<hr style="margin:32px 0 24px;">
			<h2 style="font-size:1.1rem;">📩 Email envoyé au luthier</h2>
			<div class="notice notice-info" style="padding:8px 14px;margin:8px 0 16px 0;">
				<small style="color:#555;">Variables disponibles :
				<code>{nom}</code>, <code>{email}</code>, <code>{telephone}</code>,
				<code>{modele}</code>, <code>{session}</code>, <code>{acompte}</code></small>
			</div>
			<table class="form-table">
				<tr>
					<th scope="row">Objet du mail luthier</th>
					<td><input type="text" name="stluth_luthier_subject" value="<?php echo esc_attr( get_option( 'stluth_luthier_subject', $defaults['stluth_luthier_subject'] ) ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th scope="row">Corps du mail luthier<br><small style="font-weight:normal;">(texte brut)</small></th>
					<td>
						<textarea name="stluth_luthier_body" rows="10" class="large-text code" style="font-family:monospace;font-size:13px;"><?php echo esc_textarea( get_option( 'stluth_luthier_body', $defaults['stluth_luthier_body'] ) ); ?></textarea>
						<p class="description">Texte brut envoyé au luthier avec le PDF et JSON en pièces jointes. Utilisez les variables ci-dessus.</p>
					</td>
				</tr>
			</table>

			<hr style="margin:32px 0 24px;">
			<h2 style="font-size:1.1rem;">📧 Email de confirmation stagiaire</h2>
			<div class="notice notice-info" style="padding:8px 14px;margin:8px 0 16px 0;">
				<small style="color:#555;">Variables disponibles :
				<code>{nom}</code>, <code>{modele}</code>, <code>{session}</code>,
				<code>{acompte}</code>, <code>{bank_details}</code>,
				<code>{email}</code>, <code>{telephone}</code></small>
			</div>
			<table class="form-table">
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

			<hr style="margin:32px 0 24px;">
			<h2 style="font-size:1.1rem;">🇬🇧 Email de confirmation stagiaire (English)</h2>
			<div class="notice notice-info" style="padding:8px 14px;margin:8px 0 16px 0;">
				<small style="color:#555;">Same variables available:
				<code>{nom}</code>, <code>{modele}</code>, <code>{session}</code>,
				<code>{acompte}</code>, <code>{bank_details}</code>,
				<code>{email}</code>, <code>{telephone}</code><br>
				This template is used when the English inscription form sends <code>lang=en</code>.</small>
			</div>
			<table class="form-table">
				<tr>
					<th scope="row">Subject (English)</th>
					<td><input type="text" name="stluth_confirmation_subject_en" value="<?php echo esc_attr( get_option( 'stluth_confirmation_subject_en', 'Registration confirmation — Diatonic accordion building workshop' ) ); ?>" class="large-text"></td>
				</tr>
				<tr>
					<th scope="row">Email body (English)<br><small style="font-weight:normal;">(full HTML)</small></th>
					<td>
						<?php
						$current_body_en = get_option( 'stluth_confirmation_body_en', '' );
						$body_en_for_display = ! empty( $current_body_en ) ? $current_body_en : ( function_exists( 'stluth_default_email_html_en' ) ? stluth_default_email_html_en() : '' );
						?>
						<textarea name="stluth_confirmation_body_en" rows="30" class="large-text code" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $body_en_for_display ); ?></textarea>
						<p class="description">
							Full HTML email sent to English-speaking trainees. Uses the same variables as the French version.
						</p>
					</td>
				</tr>
			</table>

		<hr style="margin:32px 0 24px;">
		<h2 style="font-size:1.1rem;">💳 Email de confirmation de paiement &amp; validation finale</h2>
		<p style="color:#555;margin-bottom:8px;">Cet email est envoyé manuellement par le luthier au stagiaire <strong>dès réception du paiement</strong>, pour confirmer que la place est définitivement réservée.</p>
		<div class="notice notice-info" style="padding:8px 14px;margin:8px 0 16px 0;">
			<small style="color:#555;">Variables disponibles :
			<code>{nom}</code>, <code>{modele}</code>, <code>{session}</code>,
			<code>{email}</code>, <code>{telephone}</code></small>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row">Objet (FR)</th>
				<td><input type="text" name="stluth_payment_confirmed_subject" value="<?php echo esc_attr( get_option( 'stluth_payment_confirmed_subject', 'Votre inscription est confirmée — Stage de fabrication d\'accordéon' ) ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th scope="row">Corps de l'email (FR)<br><small style="font-weight:normal;">(HTML complet)</small></th>
				<td>
					<?php
					$current_pc = get_option( 'stluth_payment_confirmed_body', '' );
					$pc_for_display = ! empty( $current_pc ) ? $current_pc : ( function_exists( 'stluth_default_payment_confirmed_html' ) ? stluth_default_payment_confirmed_html() : '' );
					?>
					<textarea name="stluth_payment_confirmed_body" rows="30" class="large-text code" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $pc_for_display ); ?></textarea>
					<p class="description">HTML complet de l'email de confirmation de paiement envoyé au stagiaire une fois le paiement reçu.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Subject (EN)</th>
				<td><input type="text" name="stluth_payment_confirmed_subject_en" value="<?php echo esc_attr( get_option( 'stluth_payment_confirmed_subject_en', 'Your registration is confirmed — Diatonic accordion building workshop' ) ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th scope="row">Email body (EN)<br><small style="font-weight:normal;">(full HTML)</small></th>
				<td>
					<?php
					$current_pc_en = get_option( 'stluth_payment_confirmed_body_en', '' );
					$pc_en_for_display = ! empty( $current_pc_en ) ? $current_pc_en : ( function_exists( 'stluth_default_payment_confirmed_html_en' ) ? stluth_default_payment_confirmed_html_en() : '' );
					?>
					<textarea name="stluth_payment_confirmed_body_en" rows="30" class="large-text code" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $pc_en_for_display ); ?></textarea>
					<p class="description">Full HTML email sent to English-speaking trainees upon payment confirmation.</p>
				</td>
			</tr>
		</table>

		<hr style="margin:32px 0 24px;">
		<h2 style="font-size:1.1rem;">🎓 Gestion des places par session</h2>
		<p>Les limites s'appliquent à <strong>chaque session</strong> indépendamment. Les formulaires d'inscription griseront automatiquement les modèles complets.</p>
		<div class="notice notice-info" style="padding:8px 14px;margin:8px 0 16px 0;">
			<small>Quand tous les modèles d'une session sont complets <em>ou</em> que le total est atteint, la session est grisée avec la mention <strong>Session complète</strong>.</small>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row">Max. stagiaires par session<br><small style="font-weight:normal;">(total tous modèles confondus)</small></th>
				<td>
					<input type="number" name="stluth_cap_total" value="<?php echo esc_attr( (int) get_option( 'stluth_cap_total', 15 ) ); ?>" min="1" max="99" style="width:80px;">
					<p class="description">Aucune inscription n'est acceptée au-delà de ce total pour une même session.</p>
				</td>
			</tr>
			<tr>
				<th scope="row">21 / 8 basses <small style="font-weight:normal;">(2 rangées)</small></th>
				<td><input type="number" name="stluth_cap_21_8b" value="<?php echo esc_attr( (int) get_option( 'stluth_cap_21_8b', 6 ) ); ?>" min="0" max="99" style="width:80px;"> places max par session</td>
			</tr>
			<tr>
				<th scope="row">33 / 12 basses <small style="font-weight:normal;">(3 rangées)</small></th>
				<td><input type="number" name="stluth_cap_33_12b" value="<?php echo esc_attr( (int) get_option( 'stluth_cap_33_12b', 5 ) ); ?>" min="0" max="99" style="width:80px;"> places max par session</td>
			</tr>
			<tr>
				<th scope="row">33 / 18 basses <small style="font-weight:normal;">(3 rangées)</small></th>
				<td><input type="number" name="stluth_cap_33_18b" value="<?php echo esc_attr( (int) get_option( 'stluth_cap_33_18b', 3 ) ); ?>" min="0" max="99" style="width:80px;"> places max par session</td>
			</tr>
			<tr>
				<th scope="row">33 / 24 basses <small style="font-weight:normal;">(4 rangées)</small></th>
				<td><input type="number" name="stluth_cap_33_24b" value="<?php echo esc_attr( (int) get_option( 'stluth_cap_33_24b', 3 ) ); ?>" min="0" max="99" style="width:80px;"> places max par session</td>
			</tr>
		</table>
		<?php
		/* Show current inscription counts per session */
		$known_sessions = array( 'avril2026', 'octobre2026' );
		if ( function_exists( 'stluth_count_inscriptions' ) ) {
			/* Discover additional sessions from DB */
			$db_pids = get_posts( array(
				'post_type'      => 'stluth_inscription',
				'post_status'    => array( 'publish', 'stluth_pending', 'stluth_paid' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			) );
			foreach ( $db_pids as $pid ) {
				$s = sanitize_text_field( get_post_meta( $pid, '_stluth_session', true ) );
				if ( ! empty( $s ) && ! in_array( $s, $known_sessions, true ) ) {
					$known_sessions[] = $s;
				}
			}
			echo '<h3 style="font-size:.9rem;margin-top:20px;color:#23282d;">📊 Inscriptions actuelles (hors annulées)</h3>';
			echo '<table class="widefat striped" style="margin-top:6px;max-width:680px;">';
			echo '<thead><tr><th>Session</th><th>21/8b</th><th>33/12b</th><th>33/18b</th><th>33/24b</th><th>Total</th></tr></thead><tbody>';
			$c21d  = (int) get_option( 'stluth_cap_21_8b',  6 );
			$c12d  = (int) get_option( 'stluth_cap_33_12b', 5 );
			$c18d  = (int) get_option( 'stluth_cap_33_18b', 3 );
			$c24d  = (int) get_option( 'stluth_cap_33_24b', 3 );
			$ctotd = (int) get_option( 'stluth_cap_total',  15 );
			foreach ( $known_sessions as $sess ) {
				$u21  = stluth_count_inscriptions( $sess, '21/8b' );
				$u12  = stluth_count_inscriptions( $sess, '33/12b' );
				$u18  = stluth_count_inscriptions( $sess, '33/18b' );
				$u24  = stluth_count_inscriptions( $sess, '33/24b' );
				$utot = stluth_count_inscriptions( $sess, '' );
				$style_full = 'color:#c0392b;font-weight:700;';
				echo '<tr>';
				echo '<td><strong>' . esc_html( $sess ) . '</strong></td>';
				echo '<td' . ( $u21  >= $c21d  ? ' style="' . $style_full . '"' : '' ) . '>' . $u21  . ' / ' . $c21d  . '</td>';
				echo '<td' . ( $u12  >= $c12d  ? ' style="' . $style_full . '"' : '' ) . '>' . $u12  . ' / ' . $c12d  . '</td>';
				echo '<td' . ( $u18  >= $c18d  ? ' style="' . $style_full . '"' : '' ) . '>' . $u18  . ' / ' . $c18d  . '</td>';
				echo '<td' . ( $u24  >= $c24d  ? ' style="' . $style_full . '"' : '' ) . '>' . $u24  . ' / ' . $c24d  . '</td>';
				echo '<td' . ( $utot >= $ctotd ? ' style="' . $style_full . '"' : '' ) . '><strong>' . $utot . ' / ' . $ctotd . '</strong></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		?>
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
		<form method="post" style="margin-top:8px;">
			<?php wp_nonce_field( 'stluth_reset_body_en_nonce' ); ?>
			<input type="hidden" name="stluth_reset_body_en" value="1">
			<?php submit_button( 'Reset English template to default', 'secondary', 'submit_reset_en', false ); ?>
		</form>
		<form method="post" style="margin-top:8px;">
			<?php wp_nonce_field( 'stluth_reset_payment_confirmed_nonce' ); ?>
			<input type="hidden" name="stluth_reset_payment_confirmed" value="1">
			<?php submit_button( 'Réinitialiser l\'email de confirmation de paiement (FR)', 'secondary', 'submit_reset_pc', false ); ?>
		</form>
		<form method="post" style="margin-top:8px;">
			<?php wp_nonce_field( 'stluth_reset_payment_confirmed_en_nonce' ); ?>
			<input type="hidden" name="stluth_reset_payment_confirmed_en" value="1">
			<?php submit_button( 'Reset payment confirmation email (EN)', 'secondary', 'submit_reset_pc_en', false ); ?>
		</form>

		<hr style="margin:32px 0 24px;">
		<h2 style="font-size:1rem;">🧪 Envoyer un email de test</h2>
		<p>Envoie <strong>3 emails de test</strong> à l'adresse luthier ci-dessus :</p>
		<ul style="list-style:disc;margin-left:20px;">
			<li><strong>[TEST HTML]</strong> — simule le mail de confirmation stagiaire FR (HTML + pièce jointe PDF)</li>
			<li><strong>[TEST PLAIN]</strong> — simule le mail luthier (texte brut + pièce jointe PDF)</li>
			<li><strong>[TEST EN]</strong> — simule le mail de confirmation stagiaire EN (HTML + pièce jointe PDF)</li>
		</ul>
		<p>Comparez les deux dans votre boîte de réception pour vérifier que les pièces jointes et le formatage fonctionnent.</p>
		<form method="post">
			<?php wp_nonce_field( 'stluth_test_email_nonce' ); ?>
			<input type="hidden" name="stluth_send_test" value="1">
			<?php submit_button( 'Envoyer les emails de test', 'secondary', 'submit_test', false ); ?>
		</form>
	</div>
	<?php
}

endif; // function_exists stluth_render_settings_page

/* ══════════════════════════════════════════════════════
   CUSTOM POST TYPE — stluth_inscription
   Stores every registration in the database for the
   admin to review, validate, edit, and download PDFs.
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_register_cpt' ) ) :

add_action( 'init', 'stluth_register_cpt' );

function stluth_register_cpt() {
	register_post_type( 'stluth_inscription', array(
		'labels' => array(
			'name'               => 'Inscriptions',
			'singular_name'      => 'Inscription',
			'menu_name'          => 'Inscriptions',
			'all_items'          => 'Toutes les inscriptions',
			'add_new'            => 'Ajouter',
			'add_new_item'       => 'Nouvelle inscription',
			'edit_item'          => 'Modifier l\'inscription',
			'view_item'          => 'Voir l\'inscription',
			'search_items'       => 'Rechercher',
			'not_found'          => 'Aucune inscription trouvée.',
			'not_found_in_trash' => 'Aucune inscription dans la corbeille.',
		),
		'public'             => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'menu_position'      => 25,
		'menu_icon'          => 'dashicons-clipboard',
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'supports'           => array( 'title' ),
		'has_archive'        => false,
		'rewrite'            => false,
		'query_var'          => false,
	) );

	/* Registration statuses */
	register_post_status( 'stluth_pending', array(
		'label'                     => 'En attente de paiement',
		'public'                    => false,
		'internal'                  => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		/* translators: %s: number */
		'label_count'               => _n_noop(
			'En attente <span class="count">(%s)</span>',
			'En attente <span class="count">(%s)</span>'
		),
	) );
	register_post_status( 'stluth_paid', array(
		'label'                     => 'Payée / Validée',
		'public'                    => false,
		'internal'                  => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop(
			'Validée <span class="count">(%s)</span>',
			'Validées <span class="count">(%s)</span>'
		),
	) );
	register_post_status( 'stluth_cancelled', array(
		'label'                     => 'Annulée',
		'public'                    => false,
		'internal'                  => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop(
			'Annulée <span class="count">(%s)</span>',
			'Annulées <span class="count">(%s)</span>'
		),
	) );
}

endif; // function_exists stluth_register_cpt

/* ── Save registration to CPT from the REST handler ── */

if ( ! function_exists( 'stluth_save_registration' ) ) :
/**
 * Persist a registration as a stluth_inscription post.
 *
 * @param array  $fields     Sanitized form fields.
 * @param string $pdf_path   Absolute path to the temp PDF (or empty).
 * @param string $plan_json  Raw JSON string of the keyboard plan (or empty).
 * @return int|WP_Error  Post ID on success, WP_Error on failure.
 */
function stluth_save_registration( $fields, $pdf_path = '', $plan_json = '' ) {
	$nom     = isset( $fields['nom'] )     ? $fields['nom']     : '';
	$email   = isset( $fields['email'] )   ? $fields['email']   : '';
	$modele  = isset( $fields['modele'] )  ? $fields['modele']  : '';
	$session = isset( $fields['session'] ) ? $fields['session'] : '';

	$post_id = wp_insert_post( array(
		'post_type'   => 'stluth_inscription',
		'post_title'  => $nom . ' — ' . $modele . ' — ' . $session,
		'post_status' => 'stluth_pending',
		'post_date'   => current_time( 'mysql' ),
	) );

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	/* Store every form field as post-meta */
	$meta_fields = array(
		'nom', 'email', 'telephone', 'dateNaissance', 'adresse', 'cp', 'ville',
		'session', 'modele', 'acompte', 'ancheAMano', 'upgrade2V',
		'tonalite', 'disposition', 'voix1', 'voix2', 'accordage',
		'boutonsMD', 'boisClavier', 'boisGrille', 'numGrille', 'marquageMD',
		'remarquesMD', 'nbBoutonsMG', 'couleurSangles', 'remarquesMG',
		'couleurSoufflet', 'planClavier',
	);
	foreach ( $meta_fields as $key ) {
		if ( isset( $fields[ $key ] ) ) {
			update_post_meta( $post_id, '_stluth_' . $key, sanitize_text_field( $fields[ $key ] ) );
		}
	}

	/* Persist PDF in uploads/inscriptions/YYYY/ */
	if ( ! empty( $pdf_path ) && file_exists( $pdf_path ) ) {
		$upload_dir = wp_upload_dir();
		$year       = wp_date( 'Y' );
		$dest_dir   = $upload_dir['basedir'] . '/inscriptions/' . $year;
		if ( ! file_exists( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
		}
		$safe_name = sanitize_file_name( $nom );
		$filename  = 'inscription_' . $safe_name . '_' . $post_id . '.pdf';
		$dest_path = $dest_dir . '/' . $filename;

		if ( copy( $pdf_path, $dest_path ) ) {
			$rel_url = $upload_dir['baseurl'] . '/inscriptions/' . $year . '/' . $filename;
			update_post_meta( $post_id, '_stluth_pdf_path', $dest_path );
			update_post_meta( $post_id, '_stluth_pdf_url',  $rel_url );
		}
	}

	/* Persist keyboard plan JSON — both in meta AND as a file on disk */
	if ( ! empty( $plan_json ) ) {
		update_post_meta( $post_id, '_stluth_plan_json', $plan_json );

		/* Save .json file next to the PDF for backup / security */
		$upload_dir_j = wp_upload_dir();
		$year_j       = wp_date( 'Y' );
		$dest_dir_j   = $upload_dir_j['basedir'] . '/inscriptions/' . $year_j;
		if ( ! file_exists( $dest_dir_j ) ) {
			wp_mkdir_p( $dest_dir_j );
		}
		$safe_name_j  = sanitize_file_name( $nom );
		$json_filename = 'plan_clavier_' . $safe_name_j . '_' . $post_id . '.json';
		$json_dest     = $dest_dir_j . '/' . $json_filename;

		if ( file_put_contents( $json_dest, $plan_json ) !== false ) {
			$json_url = $upload_dir_j['baseurl'] . '/inscriptions/' . $year_j . '/' . $json_filename;
			update_post_meta( $post_id, '_stluth_json_path', $json_dest );
			update_post_meta( $post_id, '_stluth_json_url',  $json_url );
		}
	}

	return $post_id;
}

endif; // function_exists stluth_save_registration

/* ── Delete associated files when an inscription is permanently deleted ── */

if ( ! function_exists( 'stluth_delete_inscription_files' ) ) :

add_action( 'before_delete_post', 'stluth_delete_inscription_files', 10, 1 );

function stluth_delete_inscription_files( $post_id ) {
	if ( 'stluth_inscription' !== get_post_type( $post_id ) ) {
		return;
	}

	$files = array(
		get_post_meta( $post_id, '_stluth_pdf_path',  true ),
		get_post_meta( $post_id, '_stluth_json_path', true ),
	);

	foreach ( $files as $file ) {
		if ( ! empty( $file ) && file_exists( $file ) ) {
			if ( ! unlink( $file ) ) {
				error_log( '[Stages Lutherie] Failed to delete file: ' . $file );
			}
		}
	}
}

endif; // function_exists stluth_delete_inscription_files

/* ── Admin columns for the inscription list ── */

if ( ! function_exists( 'stluth_inscription_columns' ) ) :

add_filter( 'manage_stluth_inscription_posts_columns', 'stluth_inscription_columns' );

function stluth_inscription_columns( $columns ) {
	$new = array();
	$new['cb']               = $columns['cb'];
	$new['title']            = 'Inscription';
	$new['stluth_email']     = 'Email';
	$new['stluth_modele']    = 'Modèle';
	$new['stluth_session']   = 'Session';
	$new['stluth_acompte']   = 'Acompte';
	$new['stluth_status']    = 'Statut';
	$new['stluth_pdf']       = 'PDF';
	$new['date']             = 'Date';
	return $new;
}

endif; // function_exists stluth_inscription_columns

if ( ! function_exists( 'stluth_inscription_column_content' ) ) :

add_action( 'manage_stluth_inscription_posts_custom_column', 'stluth_inscription_column_content', 10, 2 );

function stluth_inscription_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'stluth_email':
			$email = get_post_meta( $post_id, '_stluth_email', true );
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			break;
		case 'stluth_modele':
			echo esc_html( get_post_meta( $post_id, '_stluth_modele', true ) );
			break;
		case 'stluth_session':
			echo esc_html( get_post_meta( $post_id, '_stluth_session', true ) );
			break;
		case 'stluth_acompte':
			$acompte = get_post_meta( $post_id, '_stluth_acompte', true );
			if ( $acompte ) {
				echo esc_html( $acompte ) . '&nbsp;€';
			}
			break;
		case 'stluth_status':
			$status = get_post_status( $post_id );
			$labels = array(
				'stluth_pending'   => '<span style="color:#b26a00;font-weight:600;">⏳ En attente</span>',
				'stluth_paid'      => '<span style="color:#1a7a1a;font-weight:600;">✅ Validée</span>',
				'stluth_cancelled' => '<span style="color:#a00;font-weight:600;">❌ Annulée</span>',
			);
			echo isset( $labels[ $status ] ) ? $labels[ $status ] : esc_html( $status );
			break;
		case 'stluth_pdf':
			$pdf_url = get_post_meta( $post_id, '_stluth_pdf_url', true );
			if ( $pdf_url ) {
				echo '<a href="' . esc_url( $pdf_url ) . '" target="_blank" style="text-decoration:none;" title="Télécharger le PDF">📄 PDF</a>';
			} else {
				echo '<span style="color:#999;">—</span>';
			}
			break;
	}
}

endif; // function_exists stluth_inscription_column_content

/* ── Make columns sortable ── */

if ( ! function_exists( 'stluth_inscription_sortable_columns' ) ) :

add_filter( 'manage_edit-stluth_inscription_sortable_columns', 'stluth_inscription_sortable_columns' );

function stluth_inscription_sortable_columns( $columns ) {
	$columns['stluth_session'] = 'stluth_session';
	$columns['stluth_modele']  = 'stluth_modele';
	return $columns;
}

endif; // function_exists stluth_inscription_sortable_columns

if ( ! function_exists( 'stluth_inscription_orderby' ) ) :

add_action( 'pre_get_posts', 'stluth_inscription_orderby' );

function stluth_inscription_orderby( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'stluth_inscription' !== $query->get( 'post_type' ) ) {
		return;
	}
	$ob = $query->get( 'orderby' );
	if ( 'stluth_session' === $ob ) {
		$query->set( 'meta_key', '_stluth_session' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'stluth_modele' === $ob ) {
		$query->set( 'meta_key', '_stluth_modele' );
		$query->set( 'orderby', 'meta_value' );
	}
}

endif; // function_exists stluth_inscription_orderby

/* ── Status filter dropdown in list ── */

if ( ! function_exists( 'stluth_inscription_status_filter' ) ) :

add_action( 'restrict_manage_posts', 'stluth_inscription_status_filter' );

function stluth_inscription_status_filter( $post_type ) {
	if ( 'stluth_inscription' !== $post_type ) {
		return;
	}
	$current = isset( $_GET['post_status'] ) ? sanitize_text_field( wp_unslash( $_GET['post_status'] ) ) : '';
	$statuses = array(
		''                  => 'Tous les statuts',
		'stluth_pending'    => '⏳ En attente',
		'stluth_paid'       => '✅ Validée',
		'stluth_cancelled'  => '❌ Annulée',
	);
	echo '<select name="post_status" id="filter-stluth-status">';
	foreach ( $statuses as $val => $label ) {
		$sel = selected( $current, $val, false );
		echo '<option value="' . esc_attr( $val ) . '"' . $sel . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

endif; // function_exists stluth_inscription_status_filter

/* ── Custom status display in admin list (override WP default "published" etc.) ── */

if ( ! function_exists( 'stluth_display_post_states' ) ) :

add_filter( 'display_post_states', 'stluth_display_post_states', 10, 2 );

function stluth_display_post_states( $states, $post ) {
	if ( 'stluth_inscription' !== get_post_type( $post ) ) {
		return $states;
	}
	$status = get_post_status( $post );
	$map = array(
		'stluth_pending'   => 'En attente',
		'stluth_paid'      => 'Validée',
		'stluth_cancelled' => 'Annulée',
	);
	if ( isset( $map[ $status ] ) ) {
		return array( $map[ $status ] );
	}
	return $states;
}

endif; // function_exists stluth_display_post_states

/* ══════════════════════════════════════════════════════
   META BOXES — Edit screen for a single registration
   ══════════════════════════════════════════════════════ */

if ( ! function_exists( 'stluth_add_meta_boxes' ) ) :

add_action( 'add_meta_boxes', 'stluth_add_meta_boxes' );

function stluth_add_meta_boxes() {
	add_meta_box(
		'stluth_status_box',
		'Statut de l\'inscription',
		'stluth_render_status_box',
		'stluth_inscription',
		'side',
		'high'
	);
	add_meta_box(
		'stluth_identity_box',
		'👤 Identité du stagiaire',
		'stluth_render_identity_box',
		'stluth_inscription',
		'normal',
		'high'
	);
	add_meta_box(
		'stluth_stage_box',
		'🎵 Choix du stage',
		'stluth_render_stage_box',
		'stluth_inscription',
		'normal',
		'high'
	);
	add_meta_box(
		'stluth_specs_box',
		'🔧 Spécifications de l\'accordéon',
		'stluth_render_specs_box',
		'stluth_inscription',
		'normal',
		'default'
	);
	add_meta_box(
		'stluth_files_box',
		'📎 Fichiers',
		'stluth_render_files_box',
		'stluth_inscription',
		'side',
		'default'
	);
}

endif; // function_exists stluth_add_meta_boxes

/* ── Status meta box ── */
if ( ! function_exists( 'stluth_render_status_box' ) ) :
function stluth_render_status_box( $post ) {
	wp_nonce_field( 'stluth_save_meta', 'stluth_meta_nonce' );
	$status = get_post_status( $post->ID );
	$statuses = array(
		'stluth_pending'   => '⏳ En attente de paiement',
		'stluth_paid'      => '✅ Payée / Validée',
		'stluth_cancelled' => '❌ Annulée',
	);
	?>
	<div style="padding:8px 0;">
		<label for="stluth_status" style="font-weight:600;display:block;margin-bottom:6px;">Statut :</label>
		<select name="stluth_status" id="stluth_status" style="width:100%;padding:6px;font-size:14px;">
			<?php foreach ( $statuses as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status, $val ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description" style="margin-top:8px;">Passez en « Validée » à réception du paiement.</p>
	</div>
	<?php
}
endif;

/* ── Identity meta box ── */
if ( ! function_exists( 'stluth_render_identity_box' ) ) :
function stluth_render_identity_box( $post ) {
	$id = $post->ID;
	$fields = array(
		'nom'           => 'Nom',
		'email'         => 'Email',
		'telephone'     => 'Téléphone',
		'dateNaissance' => 'Date de naissance',
		'adresse'       => 'Adresse',
		'cp'            => 'Code postal',
		'ville'         => 'Ville',
	);
	echo '<table class="form-table" style="margin:0;">';
	foreach ( $fields as $key => $label ) {
		$val = get_post_meta( $id, '_stluth_' . $key, true );
		$type = ( $key === 'email' ) ? 'email' : 'text';
		echo '<tr><th style="width:160px;padding:8px 10px;">' . esc_html( $label ) . '</th>';
		echo '<td style="padding:8px 10px;"><input type="' . $type . '" name="stluth_field_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" class="regular-text" style="width:100%;"></td></tr>';
	}
	echo '</table>';
}
endif;

/* ── Stage choice meta box ── */
if ( ! function_exists( 'stluth_render_stage_box' ) ) :
function stluth_render_stage_box( $post ) {
	$id = $post->ID;
	$fields = array(
		'session'    => 'Session',
		'modele'     => 'Modèle',
		'acompte'    => 'Acompte (€)',
		'ancheAMano' => 'Anche a mano',
		'upgrade2V'  => 'Upgrade 2 voix',
	);
	echo '<table class="form-table" style="margin:0;">';
	foreach ( $fields as $key => $label ) {
		$val = get_post_meta( $id, '_stluth_' . $key, true );
		echo '<tr><th style="width:160px;padding:8px 10px;">' . esc_html( $label ) . '</th>';
		echo '<td style="padding:8px 10px;"><input type="text" name="stluth_field_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" class="regular-text" style="width:100%;"></td></tr>';
	}
	echo '</table>';
}
endif;

/* ── Accordion specs meta box ── */
if ( ! function_exists( 'stluth_render_specs_box' ) ) :
function stluth_render_specs_box( $post ) {
	$id = $post->ID;
	$fields = array(
		'tonalite'       => 'Tonalité',
		'disposition'    => 'Disposition',
		'voix1'          => 'Voix 1',
		'voix2'          => 'Voix 2',
		'accordage'      => 'Accordage',
		'boutonsMD'      => 'Boutons MD',
		'boisClavier'    => 'Bois clavier',
		'boisGrille'     => 'Bois grille',
		'numGrille'      => 'N° grille',
		'marquageMD'     => 'Marquage MD',
		'remarquesMD'    => 'Remarques MD',
		'nbBoutonsMG'    => 'Nb boutons MG',
		'couleurSangles' => 'Couleur sangles',
		'remarquesMG'    => 'Remarques MG',
		'couleurSoufflet' => 'Couleur soufflet',
		'planClavier'    => 'Plan clavier',
	);
	echo '<table class="form-table" style="margin:0;">';
	foreach ( $fields as $key => $label ) {
		$val = get_post_meta( $id, '_stluth_' . $key, true );
		if ( $key === 'remarquesMD' || $key === 'remarquesMG' ) {
			echo '<tr><th style="width:160px;padding:8px 10px;vertical-align:top;">' . esc_html( $label ) . '</th>';
			echo '<td style="padding:8px 10px;"><textarea name="stluth_field_' . esc_attr( $key ) . '" rows="3" class="large-text" style="width:100%;">' . esc_textarea( $val ) . '</textarea></td></tr>';
		} else {
			echo '<tr><th style="width:160px;padding:8px 10px;">' . esc_html( $label ) . '</th>';
			echo '<td style="padding:8px 10px;"><input type="text" name="stluth_field_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" class="regular-text" style="width:100%;"></td></tr>';
		}
	}
	echo '</table>';
}
endif;

/* ── Files meta box (PDF + JSON) ── */
if ( ! function_exists( 'stluth_render_files_box' ) ) :
function stluth_render_files_box( $post ) {
	$id      = $post->ID;
	$pdf_url = get_post_meta( $id, '_stluth_pdf_url', true );
	$has_json = get_post_meta( $id, '_stluth_plan_json', true );
	?>
	<div style="padding:4px 0;">
		<?php if ( $pdf_url ) : ?>
			<p><a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" class="button button-primary" style="width:100%;text-align:center;">📄 Télécharger le PDF</a></p>
		<?php else : ?>
			<p style="color:#999;">Aucun PDF disponible.</p>
		<?php endif; ?>
		<?php if ( $has_json ) : ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=stluth_download_json&post_id=' . $id . '&_wpnonce=' . wp_create_nonce( 'stluth_dl_json_' . $id ) ) ); ?>" class="button" style="width:100%;text-align:center;">🎹 Télécharger le plan JSON</a></p>
		<?php else : ?>
			<p style="color:#999;">Aucun plan de clavier JSON.</p>
		<?php endif; ?>
	</div>
	<?php
}
endif;

/* ── Save meta boxes on post save ── */

if ( ! function_exists( 'stluth_save_meta_boxes' ) ) :

add_action( 'save_post_stluth_inscription', 'stluth_save_meta_boxes', 10, 2 );

function stluth_save_meta_boxes( $post_id, $post ) {
	if ( ! isset( $_POST['stluth_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['stluth_meta_nonce'] ) ), 'stluth_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	/* Update status */
	if ( isset( $_POST['stluth_status'] ) ) {
		$new_status = sanitize_text_field( wp_unslash( $_POST['stluth_status'] ) );
		$allowed    = array( 'stluth_pending', 'stluth_paid', 'stluth_cancelled' );
		if ( in_array( $new_status, $allowed, true ) && $new_status !== get_post_status( $post_id ) ) {
			/* remove_action to avoid infinite loop */
			remove_action( 'save_post_stluth_inscription', 'stluth_save_meta_boxes', 10 );
			wp_update_post( array(
				'ID'          => $post_id,
				'post_status' => $new_status,
			) );
			add_action( 'save_post_stluth_inscription', 'stluth_save_meta_boxes', 10, 2 );
		}
	}

	/* Update editable meta fields */
	$meta_fields = array(
		'nom', 'email', 'telephone', 'dateNaissance', 'adresse', 'cp', 'ville',
		'session', 'modele', 'acompte', 'ancheAMano', 'upgrade2V',
		'tonalite', 'disposition', 'voix1', 'voix2', 'accordage',
		'boutonsMD', 'boisClavier', 'boisGrille', 'numGrille', 'marquageMD',
		'remarquesMD', 'nbBoutonsMG', 'couleurSangles', 'remarquesMG',
		'couleurSoufflet', 'planClavier',
	);
	foreach ( $meta_fields as $key ) {
		$field_name = 'stluth_field_' . $key;
		if ( isset( $_POST[ $field_name ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
			update_post_meta( $post_id, '_stluth_' . $key, $val );
		}
	}
}

endif; // function_exists stluth_save_meta_boxes

/* ── JSON download via AJAX ── */

if ( ! function_exists( 'stluth_download_json' ) ) :

add_action( 'wp_ajax_stluth_download_json', 'stluth_download_json' );

function stluth_download_json() {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Accès refusé.', 403 );
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stluth_dl_json_' . $post_id ) ) {
		wp_die( 'Nonce invalide.', 403 );
	}
	$json = get_post_meta( $post_id, '_stluth_plan_json', true );
	if ( empty( $json ) ) {
		wp_die( 'Aucun plan JSON.', 404 );
	}
	/* Validate that stored value is valid JSON before outputting */
	$decoded = json_decode( $json );
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		wp_die( 'Données JSON invalides.', 500 );
	}
	$nom = get_post_meta( $post_id, '_stluth_nom', true );
	$filename = 'plan_clavier_' . sanitize_file_name( $nom ? $nom : 'inscription' ) . '.json';
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	echo wp_json_encode( $decoded );
	exit;
}

endif; // function_exists stluth_download_json

/* ── Row actions — quick validate / cancel ── */

if ( ! function_exists( 'stluth_row_actions' ) ) :

add_filter( 'post_row_actions', 'stluth_row_actions', 10, 2 );

function stluth_row_actions( $actions, $post ) {
	if ( 'stluth_inscription' !== get_post_type( $post ) ) {
		return $actions;
	}
	$status = get_post_status( $post );
	if ( 'stluth_paid' !== $status ) {
		$url = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=stluth_quick_status&post_id=' . $post->ID . '&new_status=stluth_paid' ),
			'stluth_quick_' . $post->ID
		);
		$actions['stluth_validate'] = '<a href="' . esc_url( $url ) . '" style="color:#1a7a1a;font-weight:600;">✅ Valider paiement</a>';
	}
	if ( 'stluth_cancelled' !== $status ) {
		$url = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=stluth_quick_status&post_id=' . $post->ID . '&new_status=stluth_cancelled' ),
			'stluth_quick_' . $post->ID
		);
		$actions['stluth_cancel'] = '<a href="' . esc_url( $url ) . '" style="color:#a00;">❌ Annuler</a>';
	}
	if ( 'stluth_pending' !== $status ) {
		$url = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=stluth_quick_status&post_id=' . $post->ID . '&new_status=stluth_pending' ),
			'stluth_quick_' . $post->ID
		);
		$actions['stluth_pending'] = '<a href="' . esc_url( $url ) . '" style="color:#b26a00;">⏳ Remettre en attente</a>';
	}

	/* PDF link in row actions too */
	$pdf_url = get_post_meta( $post->ID, '_stluth_pdf_url', true );
	if ( $pdf_url ) {
		$actions['stluth_pdf'] = '<a href="' . esc_url( $pdf_url ) . '" target="_blank">📄 PDF</a>';
	}

	return $actions;
}

endif; // function_exists stluth_row_actions

/* ── Quick status change via AJAX ── */

if ( ! function_exists( 'stluth_quick_status' ) ) :

add_action( 'wp_ajax_stluth_quick_status', 'stluth_quick_status' );

function stluth_quick_status() {
	$post_id    = isset( $_GET['post_id'] )    ? absint( $_GET['post_id'] ) : 0;
	$new_status = isset( $_GET['new_status'] ) ? sanitize_text_field( wp_unslash( $_GET['new_status'] ) ) : '';

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'Accès refusé.', 403 );
	}
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'stluth_quick_' . $post_id ) ) {
		wp_die( 'Nonce invalide.', 403 );
	}
	$allowed = array( 'stluth_pending', 'stluth_paid', 'stluth_cancelled' );
	if ( ! in_array( $new_status, $allowed, true ) ) {
		wp_die( 'Statut invalide.', 400 );
	}
	wp_update_post( array(
		'ID'          => $post_id,
		'post_status' => $new_status,
	) );

	/* Redirect back to the list */
	$redirect = admin_url( 'edit.php?post_type=stluth_inscription&stluth_updated=1' );
	wp_safe_redirect( $redirect );
	exit;
}

endif; // function_exists stluth_quick_status

/* ── Admin notice after quick action ── */

if ( ! function_exists( 'stluth_admin_notices' ) ) :

add_action( 'admin_notices', 'stluth_admin_notices' );

function stluth_admin_notices() {
	if ( isset( $_GET['stluth_updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['stluth_updated'] ) ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>✅ Statut de l\'inscription mis à jour.</p></div>';
	}
}

endif; // function_exists stluth_admin_notices

/* ── Remove the default "Publish" meta box and replace with status ── */

if ( ! function_exists( 'stluth_remove_publish_box' ) ) :

add_action( 'add_meta_boxes_stluth_inscription', 'stluth_remove_publish_box' );

function stluth_remove_publish_box() {
	remove_meta_box( 'submitdiv', 'stluth_inscription', 'side' );
	add_meta_box(
		'stluth_submitdiv',
		'Enregistrer',
		'stluth_render_submit_box',
		'stluth_inscription',
		'side',
		'high'
	);
}

endif; // function_exists stluth_remove_publish_box

if ( ! function_exists( 'stluth_render_submit_box' ) ) :
function stluth_render_submit_box( $post ) {
	?>
	<div style="padding:8px 0;">
		<div style="margin-bottom:10px;">
			<span style="color:#666;">Créée le :</span>
			<strong><?php echo esc_html( get_the_date( 'j F Y à H:i', $post ) ); ?></strong>
		</div>
		<div style="display:flex;gap:8px;">
			<input type="submit" name="save" class="button button-primary button-large" value="Enregistrer" style="flex:1;">
			<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
				<a href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>" class="button button-link-delete" style="color:#a00;">Supprimer</a>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
endif;

/* ── Dashboard widget: recent inscriptions summary ── */

if ( ! function_exists( 'stluth_dashboard_widget' ) ) :

add_action( 'wp_dashboard_setup', 'stluth_dashboard_widget' );

function stluth_dashboard_widget() {
	wp_add_dashboard_widget(
		'stluth_inscriptions_widget',
		'📋 Inscriptions Stage',
		'stluth_render_dashboard_widget'
	);
}

endif; // function_exists stluth_dashboard_widget

if ( ! function_exists( 'stluth_render_dashboard_widget' ) ) :
function stluth_render_dashboard_widget() {
	global $wpdb;
	$counts = array(
		'stluth_pending'   => 0,
		'stluth_paid'      => 0,
		'stluth_cancelled' => 0,
	);
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_status, COUNT(*) as cnt FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status",
			'stluth_inscription'
		)
	);
	if ( $results ) {
		foreach ( $results as $row ) {
			if ( isset( $counts[ $row->post_status ] ) ) {
				$counts[ $row->post_status ] = (int) $row->cnt;
			}
		}
	}
	$total = array_sum( $counts );
	$list_url = admin_url( 'edit.php?post_type=stluth_inscription' );
	?>
	<div style="display:flex;gap:12px;margin-bottom:12px;">
		<div style="flex:1;text-align:center;padding:12px;background:#fff8e6;border-radius:6px;border:1px solid #e0d4c4;">
			<div style="font-size:28px;font-weight:700;color:#b26a00;"><?php echo esc_html( $counts['stluth_pending'] ); ?></div>
			<div style="font-size:12px;color:#7a6a55;">En attente</div>
		</div>
		<div style="flex:1;text-align:center;padding:12px;background:#e8f5e3;border-radius:6px;border:1px solid #c3e6b5;">
			<div style="font-size:28px;font-weight:700;color:#1a7a1a;"><?php echo esc_html( $counts['stluth_paid'] ); ?></div>
			<div style="font-size:12px;color:#2d5a27;">Validées</div>
		</div>
		<div style="flex:1;text-align:center;padding:12px;background:#fce8e8;border-radius:6px;border:1px solid #e5c3c3;">
			<div style="font-size:28px;font-weight:700;color:#a00;"><?php echo esc_html( $counts['stluth_cancelled'] ); ?></div>
			<div style="font-size:12px;color:#7a3535;">Annulées</div>
		</div>
	</div>
	<p style="text-align:center;margin:0;">
		<strong><?php echo esc_html( $total ); ?></strong> inscription(s) au total —
		<a href="<?php echo esc_url( $list_url ); ?>">Voir toutes les inscriptions →</a>
	</p>
	<?php
	/* Last 5 pending */
	$recent = get_posts( array(
		'post_type'   => 'stluth_inscription',
		'post_status' => 'stluth_pending',
		'numberposts' => 5,
		'orderby'     => 'date',
		'order'       => 'DESC',
	) );
	if ( $recent ) {
		echo '<hr style="margin:12px 0;">';
		echo '<p style="font-weight:600;margin-bottom:6px;">⏳ En attente de paiement :</p>';
		echo '<ul style="margin:0;">';
		foreach ( $recent as $r ) {
			$name  = get_post_meta( $r->ID, '_stluth_nom', true );
			$model = get_post_meta( $r->ID, '_stluth_modele', true );
			$edit  = get_edit_post_link( $r->ID );
			echo '<li style="margin-bottom:4px;"><a href="' . esc_url( $edit ) . '">' . esc_html( $name ) . '</a> <span style="color:#888;">(' . esc_html( $model ) . ')</span></li>';
		}
		echo '</ul>';
	}
}
endif;

/* ── Admin CSS for the inscription list ── */

if ( ! function_exists( 'stluth_admin_css' ) ) :

add_action( 'admin_head', 'stluth_admin_css' );

function stluth_admin_css() {
	$screen = get_current_screen();
	if ( ! $screen || 'stluth_inscription' !== $screen->post_type ) {
		return;
	}
	?>
	<style>
		.column-stluth_status { width: 120px; }
		.column-stluth_pdf    { width: 60px; text-align: center; }
		.column-stluth_acompte { width: 90px; }
		/* Hide default status dropdown in quick edit — we use our own */
		.inline-edit-status { display: none !important; }
	</style>
	<?php
}

endif; // function_exists stluth_admin_css
