<?php
/**
 * Plugin Name: EVD — Bloc bilingue + Seed
 * Description: Bloc Gutenberg FR/EN, système de langue et seed SQL pour ewendaviau.com
 * Version: 1.0
 *
 * Déploiement : copier ce fichier dans wp-content/mu-plugins/evd.php
 * WordPress le charge automatiquement — aucune activation, functions.php inchangé.
 *
 * Copier aussi assets/block.js et includes/seed.php dans le thème actif.
 */

// ── Bloc Gutenberg dynamique bilingue ewendaviau/html-libre ───────────────────

add_action( 'init', function () {
    wp_register_script( 'evd-block', get_template_directory_uri() . '/assets/block.js',
        [ 'wp-blocks', 'wp-element' ], '1.0.4', false );

    register_block_type( 'ewendaviau/html-libre', [
        'editor_script'   => 'evd-block',
        'attributes'      => [ 'html' => [ 'type' => 'string', 'default' => '' ], 'html_en' => [ 'type' => 'string', 'default' => '' ] ],
        'render_callback' => function ( $a ) {
            $en = evd_lang() === 'en' && ( $a['html_en'] ?? '' ) !== '';
            return $en ? $a['html_en'] : ( $a['html'] ?? '' );
        },
    ] );
} );

// ── Langue FR/EN ──────────────────────────────────────────────────────────────

function evd_lang(): string {
    if ( isset( $_GET['lang'] ) && in_array( $_GET['lang'], [ 'fr', 'en' ], true ) )
        return sanitize_key( $_GET['lang'] );
    return ( $_COOKIE['evd_lang'] ?? 'fr' ) === 'en' ? 'en' : 'fr';
}

add_action( 'wp_head', function () {
    $l = evd_lang();
    if ( isset( $_GET['lang'] ) )
        setcookie( 'evd_lang', $l, time() + 365 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
    echo '<script>(function(){document.documentElement.classList.add("lang-' . esc_js( $l ) . '")})();</script>' . "\n";
    echo '<style>.evd-lang-en{display:none}html.lang-en .evd-lang-fr{display:none}html.lang-en .evd-lang-en{display:block}</style>' . "\n";
}, 1 );

// ── Sessions — injection front-end ───────────────────────────────────────────

add_action( 'wp_head', function () {
    $sessions = get_option( 'stluth_sessions', [] );
    $annee    = (int) date( 'Y' );
    if ( ! empty( $sessions ) ) {
        preg_match( '/(\d{4})$/', $sessions[0]['fullId'] ?? '', $m );
        if ( ! empty( $m[1] ) ) $annee = (int) $m[1];
    }
    echo '<script>window.STLUTH_ACTIVE_SESSIONS=' . wp_json_encode( [ 'annee' => $annee, 'sessions' => $sessions ] ) . ';</script>' . "\n";
}, 5 );

// ── REST : endpoint inscription ───────────────────────────────────────────────

add_action( 'rest_api_init', function () {
    register_rest_route( 'stages-lutherie/v1', '/inscription', [
        'methods'             => 'POST',
        'callback'            => 'stluth_handle_inscription_rest',
        'permission_callback' => '__return_true',
    ] );
} );

function stluth_handle_inscription_rest( WP_REST_Request $request ): WP_REST_Response {
    $body = $request->get_json_params();
    if ( ! is_array( $body ) ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Corps JSON invalide' ], 400 );
    }

    $fields  = is_array( $body['fields'] ?? null ) ? $body['fields'] : [];
    $pdf_b64 = is_string( $body['pdfBase64'] ?? null ) ? $body['pdfBase64'] : '';
    $nom     = sanitize_text_field( $fields['nom']   ?? '' );
    $email   = sanitize_email( $fields['email']       ?? '' );

    if ( ! $email ) {
        return new WP_REST_Response( [ 'success' => false, 'message' => 'Email manquant' ], 400 );
    }

    // Decode PDF for attachment
    $attachments = [];
    if ( $pdf_b64 ) {
        $pdf_raw = base64_decode( preg_replace( '#^data:[^;]+;base64,#', '', $pdf_b64 ) );
        if ( $pdf_raw ) {
            $tmp = sys_get_temp_dir() . '/stluth_insc_' . time() . '_' . wp_rand( 1000, 9999 ) . '.pdf';
            if ( file_put_contents( $tmp, $pdf_raw ) !== false ) {
                $attachments[] = $tmp;
            }
        }
    }

    $notif_email = sanitize_email( (string) get_option( 'stluth_mail_notif_email', 'contact@ewendaviau.com' ) );
    $rib         = (string) get_option( 'stluth_mail_rib', '' );
    $modele      = sanitize_text_field( $fields['modele']  ?? '' );
    $session_id  = sanitize_text_field( $fields['session'] ?? '' );
    $dates       = sanitize_text_field( $fields['dates']   ?? '' );

    $replace = [
        '{{NOM}}'     => $nom,
        '{{MODELE}}'  => $modele,
        '{{SESSION}}' => $session_id,
        '{{DATES}}'   => $dates,
        '{{RIB}}'     => $rib,
    ];

    $subject_fr = (string) get_option( 'stluth_mail_subject_fr', stluth_mail_default_subject_fr() );
    $body_fr    = (string) get_option( 'stluth_mail_body_fr',    stluth_mail_default_body_fr() );
    $subject_en = (string) get_option( 'stluth_mail_subject_en', stluth_mail_default_subject_en() );
    $body_en    = (string) get_option( 'stluth_mail_body_en',    stluth_mail_default_body_en() );

    // Use EN if session/locale signals it (simple heuristic: check email field locale)
    $use_en   = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) && str_starts_with( strtolower( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ), 'en' );
    $subject  = str_replace( array_keys( $replace ), array_values( $replace ), $use_en ? $subject_en : $subject_fr );
    $body_tpl = str_replace( array_keys( $replace ), array_values( $replace ), $use_en ? $body_en : $body_fr );

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Ewen Daviau <' . $notif_email . '>',
    ];

    // Confirmation to participant
    wp_mail( $email, $subject, nl2br( esc_html( $body_tpl ) ), $headers, $attachments );

    // Notification to instructor
    $notif_lines = [];
    foreach ( $fields as $k => $v ) {
        if ( $v !== '' && $v !== null ) {
            $notif_lines[] = '<strong>' . esc_html( $k ) . '</strong> : ' . esc_html( (string) $v );
        }
    }
    wp_mail(
        $notif_email,
        'Nouvelle inscription stage — ' . $nom . ' — ' . $modele,
        implode( '<br>', $notif_lines ),
        $headers,
        $attachments
    );

    foreach ( $attachments as $f ) {
        if ( file_exists( $f ) ) {
            @unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
    }

    return new WP_REST_Response( [ 'success' => true ], 200 );
}

function stluth_mail_default_subject_fr(): string {
    return 'Votre inscription au stage de lutherie — Ewen Daviau';
}

function stluth_mail_default_subject_en(): string {
    return 'Your lutherie workshop registration — Ewen Daviau';
}

function stluth_mail_default_body_fr(): string {
    return "Bonjour {{NOM}},\n\nVotre inscription au stage de lutherie accordéon diatonique a bien été reçue.\n\n"
         . "Modèle : {{MODELE}}\nSession : {{SESSION}} — {{DATES}}\n\n"
         . "Pour confirmer votre place, merci de régler l'acompte (40 %) par virement bancaire :\n\n{{RIB}}\n\n"
         . "N'hésitez pas à me contacter pour toute question.\n\n"
         . "À bientôt,\nEwen Daviau\ncontact@ewendaviau.com";
}

function stluth_mail_default_body_en(): string {
    return "Hello {{NOM}},\n\nYour registration for the diatonic accordion lutherie workshop has been received.\n\n"
         . "Model: {{MODELE}}\nSession: {{SESSION}} — {{DATES}}\n\n"
         . "To confirm your place, please transfer the deposit (40%) to:\n\n{{RIB}}\n\n"
         . "Feel free to get in touch if you have any questions.\n\n"
         . "See you soon,\nEwen Daviau\ncontact@ewendaviau.com";
}

// ── Admin : save sessions + tarif ─────────────────────────────────────────────

add_action( 'admin_post_stluth_params_save', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'stluth_params_save' );

    update_option( 'stluth_tarif_retour', max( 0, (int) ( $_POST['stluth_tarif_retour'] ?? 80 ) ) );

    $sessions = [];
    foreach ( (array) ( $_POST['s_fullId'] ?? [] ) as $i => $fullId ) {
        $fullId = sanitize_key( $fullId );
        if ( ! $fullId ) continue;
        $sessions[] = [
            'id'       => preg_replace( '/\d+$/', '', $fullId ),
            'fullId'   => $fullId,
            'icon'     => sanitize_text_field( $_POST['s_icon'][ $i ]     ?? '' ),
            'saison'   => sanitize_text_field( $_POST['s_saison'][ $i ]   ?? '' ),
            'saisonEn' => sanitize_text_field( $_POST['s_saisonEn'][ $i ] ?? '' ),
            'dates'    => sanitize_text_field( $_POST['s_dates'][ $i ]    ?? '' ),
            'datesEn'  => sanitize_text_field( $_POST['s_datesEn'][ $i ]  ?? '' ),
        ];
    }
    update_option( 'stluth_sessions', $sessions );

    wp_redirect( admin_url( 'admin.php?page=evd-seed&tab=sessions&saved=1' ) );
    exit;
} );

// ── Admin : save mail config ───────────────────────────────────────────────────

add_action( 'admin_post_stluth_mail_save', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'stluth_mail_save' );

    update_option( 'stluth_mail_notif_email', sanitize_email( $_POST['stluth_mail_notif_email'] ?? 'contact@ewendaviau.com' ) );
    update_option( 'stluth_mail_rib',         sanitize_textarea_field( $_POST['stluth_mail_rib']         ?? '' ) );
    update_option( 'stluth_mail_subject_fr',  sanitize_text_field(     $_POST['stluth_mail_subject_fr']  ?? '' ) );
    update_option( 'stluth_mail_body_fr',     sanitize_textarea_field( $_POST['stluth_mail_body_fr']     ?? '' ) );
    update_option( 'stluth_mail_subject_en',  sanitize_text_field(     $_POST['stluth_mail_subject_en']  ?? '' ) );
    update_option( 'stluth_mail_body_en',     sanitize_textarea_field( $_POST['stluth_mail_body_en']     ?? '' ) );

    wp_redirect( admin_url( 'admin.php?page=evd-seed&tab=mails&saved=1' ) );
    exit;
} );

// ── Admin : page tabulée Stages › Sessions | Seed | Mails ────────────────────

function evd_render_seed_page(): void {
    $tab      = sanitize_key( $_GET['tab'] ?? 'sessions' );
    $sessions = get_option( 'stluth_sessions', [] );
    $tarif    = (int) get_option( 'stluth_tarif_retour', 80 );
    $page_url = admin_url( 'admin.php?page=evd-seed' );

    if ( isset( $_GET['seeded'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Seed exécuté.</p></div>';
    if ( isset( $_GET['saved'] ) )  echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés.</p></div>';
    ?>
    <div class="wrap">
    <h1>Stages ewendaviau</h1>

    <nav class="nav-tab-wrapper" style="margin-bottom:1.5rem">
      <a href="<?php echo esc_url( $page_url . '&tab=sessions' ); ?>"
         class="nav-tab <?php echo $tab === 'sessions' ? 'nav-tab-active' : ''; ?>">Sessions</a>
      <a href="<?php echo esc_url( $page_url . '&tab=seed' ); ?>"
         class="nav-tab <?php echo $tab === 'seed' ? 'nav-tab-active' : ''; ?>">Seed</a>
      <a href="<?php echo esc_url( $page_url . '&tab=mails' ); ?>"
         class="nav-tab <?php echo $tab === 'mails' ? 'nav-tab-active' : ''; ?>">Mails</a>
    </nav>

    <?php if ( $tab === 'sessions' ) : ?>

      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="stluth_params_save">
        <?php wp_nonce_field( 'stluth_params_save' ); ?>

        <h2 style="margin-top:0">Sessions actives</h2>
        <p class="description" style="margin-bottom:1rem">
          Injectées dans le formulaire d'inscription via <code>window.STLUTH_ACTIVE_SESSIONS</code>.
          Laisser vide → affiche «&nbsp;Dates disponibles prochainement&nbsp;».
        </p>

        <table class="widefat striped" id="stluth-sess-tbl" style="max-width:100%;margin:0 0 1rem">
          <thead><tr>
            <th>ID <small style="font-weight:400">(ex: octobre2026)</small></th>
            <th>Icône</th>
            <th>Saison FR</th>
            <th>Saison EN</th>
            <th>Dates FR</th>
            <th>Dates EN</th>
            <th></th>
          </tr></thead>
          <tbody id="stluth-sess-body">
          <?php foreach ( $sessions as $s ) : ?>
            <tr>
              <td><input type="text" name="s_fullId[]"   value="<?php echo esc_attr( $s['fullId']   ?? '' ); ?>" placeholder="octobre2026" style="width:110px"></td>
              <td><input type="text" name="s_icon[]"     value="<?php echo esc_attr( $s['icon']     ?? '' ); ?>" placeholder="🍂" style="width:44px"></td>
              <td><input type="text" name="s_saison[]"   value="<?php echo esc_attr( $s['saison']   ?? '' ); ?>" placeholder="Automne" style="width:95px"></td>
              <td><input type="text" name="s_saisonEn[]" value="<?php echo esc_attr( $s['saisonEn'] ?? '' ); ?>" placeholder="Autumn" style="width:95px"></td>
              <td><input type="text" name="s_dates[]"    value="<?php echo esc_attr( $s['dates']    ?? '' ); ?>" placeholder="Merc. 14 – Vend. 23 oct. 2026" style="width:210px"></td>
              <td><input type="text" name="s_datesEn[]"  value="<?php echo esc_attr( $s['datesEn']  ?? '' ); ?>" placeholder="Wed 14 – Fri 23 Oct 2026" style="width:200px"></td>
              <td><button type="button" class="button button-small stluth-del-row">–</button></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <button type="button" class="button" id="stluth-add-sess">+ Ajouter une session</button>

        <h2 style="margin-top:2rem">Tarifs</h2>
        <table class="form-table" style="max-width:580px"><tbody>
          <tr>
            <th><label for="stluth_tarif_retour">Retour atelier</label></th>
            <td>
              <input type="number" id="stluth_tarif_retour" name="stluth_tarif_retour"
                     value="<?php echo esc_attr( $tarif ); ?>" min="0" step="1" class="small-text"> €/jour
              <p class="description">
                Remplace <code>{{TARIF_RETOUR}}</code> dans toutes les pages au prochain seed.<br>
                Affiché dans les stages, inscriptions et CGV (Art. 4).
              </p>
            </td>
          </tr>
        </tbody></table>

        <p style="margin-top:1.5rem">
          <button type="submit" class="button button-primary">Enregistrer</button>
        </p>
      </form>

      <script>
      (function(){
        var tpl = '<td><input type="text" name="s_fullId[]" placeholder="octobre2026" style="width:110px"></td>'
          + '<td><input type="text" name="s_icon[]" placeholder="🍂" style="width:44px"></td>'
          + '<td><input type="text" name="s_saison[]" placeholder="Automne" style="width:95px"></td>'
          + '<td><input type="text" name="s_saisonEn[]" placeholder="Autumn" style="width:95px"></td>'
          + '<td><input type="text" name="s_dates[]" placeholder="Merc. 14 – Vend. 23 oct. 2026" style="width:210px"></td>'
          + '<td><input type="text" name="s_datesEn[]" placeholder="Wed 14 – Fri 23 Oct 2026" style="width:200px"></td>'
          + '<td><button type="button" class="button button-small stluth-del-row">–</button></td>';
        var tbody = document.getElementById('stluth-sess-body');
        function bindDel(btn){ btn.addEventListener('click', function(){ this.closest('tr').remove(); }); }
        document.querySelectorAll('.stluth-del-row').forEach(bindDel);
        document.getElementById('stluth-add-sess').addEventListener('click', function(){
          var tr = document.createElement('tr'); tr.innerHTML = tpl; tbody.appendChild(tr); bindDel(tr.querySelector('.stluth-del-row'));
        });
      })();
      </script>

    <?php elseif ( $tab === 'seed' ) : ?>

      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="evd_seed_run">
        <?php wp_nonce_field( 'evd_seed_run' ); ?>
        <p class="description" style="margin-bottom:1rem">
          Écrase le contenu des pages WP avec les fichiers HTML du repo + paramètres actuels.
        </p>
        <select name="seed" style="margin-right:.5rem">
          <option value="all">Tout le site</option>
          <option value="stages">Stages FR</option>
          <option value="stages_en">Stages EN</option>
          <option value="inscriptions">Inscriptions FR</option>
          <option value="inscriptions_en">Inscriptions EN</option>
          <option value="cgv">CGV (FR + EN)</option>
        </select>
        <button class="button button-primary">Lancer le seed</button>
      </form>

    <?php elseif ( $tab === 'mails' ) : ?>

      <?php
      $notif_email = (string) get_option( 'stluth_mail_notif_email', 'contact@ewendaviau.com' );
      $rib         = (string) get_option( 'stluth_mail_rib',         '' );
      $subj_fr     = (string) get_option( 'stluth_mail_subject_fr',  stluth_mail_default_subject_fr() );
      $body_fr     = (string) get_option( 'stluth_mail_body_fr',     stluth_mail_default_body_fr() );
      $subj_en     = (string) get_option( 'stluth_mail_subject_en',  stluth_mail_default_subject_en() );
      $body_en     = (string) get_option( 'stluth_mail_body_en',     stluth_mail_default_body_en() );
      ?>
      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="stluth_mail_save">
        <?php wp_nonce_field( 'stluth_mail_save' ); ?>

        <p class="description" style="margin-bottom:1.5rem">
          Envoyé automatiquement quand un stagiaire soumet le formulaire d'inscription.<br>
          Variables disponibles : <code>{{NOM}}</code>, <code>{{MODELE}}</code>, <code>{{SESSION}}</code>, <code>{{DATES}}</code>, <code>{{RIB}}</code>
        </p>

        <table class="form-table"><tbody>
          <tr>
            <th><label for="stluth_mail_notif_email">Email de notification</label></th>
            <td>
              <input type="email" id="stluth_mail_notif_email" name="stluth_mail_notif_email"
                     value="<?php echo esc_attr( $notif_email ); ?>" class="regular-text">
              <p class="description">Reçoit une copie de chaque inscription. Utilisé aussi comme expéditeur.</p>
            </td>
          </tr>
          <tr>
            <th><label for="stluth_mail_rib">Coordonnées bancaires (RIB)</label></th>
            <td>
              <textarea id="stluth_mail_rib" name="stluth_mail_rib" rows="5"
                        class="large-text"><?php echo esc_textarea( $rib ); ?></textarea>
              <p class="description">Remplace <code>{{RIB}}</code> dans le corps de l'email.</p>
            </td>
          </tr>
          <tr>
            <th style="padding-top:2rem"><strong>Email FR</strong></th>
            <td></td>
          </tr>
          <tr>
            <th><label for="stluth_mail_subject_fr">Objet (FR)</label></th>
            <td>
              <input type="text" id="stluth_mail_subject_fr" name="stluth_mail_subject_fr"
                     value="<?php echo esc_attr( $subj_fr ); ?>" class="large-text">
            </td>
          </tr>
          <tr>
            <th><label for="stluth_mail_body_fr">Corps (FR)</label></th>
            <td>
              <textarea id="stluth_mail_body_fr" name="stluth_mail_body_fr" rows="12"
                        class="large-text"><?php echo esc_textarea( $body_fr ); ?></textarea>
            </td>
          </tr>
          <tr>
            <th style="padding-top:2rem"><strong>Email EN</strong></th>
            <td></td>
          </tr>
          <tr>
            <th><label for="stluth_mail_subject_en">Objet (EN)</label></th>
            <td>
              <input type="text" id="stluth_mail_subject_en" name="stluth_mail_subject_en"
                     value="<?php echo esc_attr( $subj_en ); ?>" class="large-text">
            </td>
          </tr>
          <tr>
            <th><label for="stluth_mail_body_en">Corps (EN)</label></th>
            <td>
              <textarea id="stluth_mail_body_en" name="stluth_mail_body_en" rows="12"
                        class="large-text"><?php echo esc_textarea( $body_en ); ?></textarea>
            </td>
          </tr>
        </tbody></table>

        <p style="margin-top:1rem">
          <button type="submit" class="button button-primary">Enregistrer</button>
        </p>
      </form>

    <?php endif; ?>
    </div>
    <?php
}

function evd_add_seed_admin_page(): void {
    global $admin_page_hooks;
    if ( isset( $admin_page_hooks['stluth-stages'] ) ) {
        add_submenu_page( 'stluth-stages', 'Seed', 'Seed', 'manage_options', 'evd-seed', 'evd_render_seed_page' );
        return;
    }
    add_management_page( 'Seed', 'Seed ewendaviau', 'manage_options', 'evd-seed', 'evd_render_seed_page' );
}
add_action( 'admin_menu', 'evd_add_seed_admin_page', 20 );

add_action( 'admin_post_evd_seed_run', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'evd_seed_run' );
    try {
        require_once get_template_directory() . '/includes/seed.php';
        evd_run_seed();
    } catch ( \Throwable $e ) {
        wp_die(
            '<h2>Erreur seed</h2><pre>' . esc_html( $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() ) . '</pre>',
            'Seed error', [ 'back_link' => true ]
        );
    }
    wp_redirect( admin_url( 'admin.php?page=evd-seed&tab=seed&seeded=1' ) );
    exit;
} );
