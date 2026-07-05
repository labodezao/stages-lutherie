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

// ── Admin : Paramètres stages (sessions + tarifs) ─────────────────────────────

function stluth_render_params_page(): void {
    if ( isset( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés.</p></div>';
    }
    $sessions = get_option( 'stluth_sessions', [] );
    $tarif    = (int) get_option( 'stluth_tarif_retour', 80 );
    ?>
    <div class="wrap">
    <h1>Paramètres — Stages ewendaviau</h1>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <input type="hidden" name="action" value="stluth_params_save">
    <?php wp_nonce_field( 'stluth_params_save' ); ?>

    <h2 style="margin-top:1.5rem">Tarifs</h2>
    <table class="form-table"><tbody>
    <tr>
      <th><label for="stluth_tarif_retour">Retour atelier (€/jour)</label></th>
      <td>
        <input type="number" id="stluth_tarif_retour" name="stluth_tarif_retour"
               value="<?php echo esc_attr( $tarif ); ?>" min="0" step="1" class="small-text"> €/jour
        <p class="description">Apparaît dans les CGV (Art. 4) après un seed.</p>
      </td>
    </tr>
    </tbody></table>

    <h2 style="margin-top:2rem">Sessions actives</h2>
    <p class="description">Sessions affichées dans le formulaire d'inscription.<br>
    Laissez vide pour afficher «&nbsp;Dates disponibles prochainement&nbsp;».</p>

    <table class="widefat striped" id="stluth-sess-tbl" style="max-width:960px;margin:1rem 0">
      <thead><tr>
        <th>ID complet <small>(ex: octobre2026)</small></th>
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
          <td><input type="text" name="s_fullId[]" value="<?php echo esc_attr( $s['fullId'] ?? '' ); ?>" placeholder="octobre2026" style="width:110px"></td>
          <td><input type="text" name="s_icon[]"   value="<?php echo esc_attr( $s['icon']   ?? '' ); ?>" placeholder="🍂" style="width:48px"></td>
          <td><input type="text" name="s_saison[]"   value="<?php echo esc_attr( $s['saison']   ?? '' ); ?>" placeholder="Automne" style="width:100px"></td>
          <td><input type="text" name="s_saisonEn[]" value="<?php echo esc_attr( $s['saisonEn'] ?? '' ); ?>" placeholder="Autumn"  style="width:100px"></td>
          <td><input type="text" name="s_dates[]"   value="<?php echo esc_attr( $s['dates']   ?? '' ); ?>" placeholder="Merc. 14 – Vend. 23 oct. 2026" style="width:220px"></td>
          <td><input type="text" name="s_datesEn[]" value="<?php echo esc_attr( $s['datesEn'] ?? '' ); ?>" placeholder="Wed 14 – Fri 23 Oct 2026"      style="width:210px"></td>
          <td><button type="button" class="button button-small stluth-del-row">–</button></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="button" id="stluth-add-sess">+ Ajouter une session</button>

    <p style="margin-top:2rem">
      <button type="submit" class="button button-primary">Enregistrer</button>
    </p>
    </form>
    </div>

    <script>
    (function(){
      var tpl = [ '<td><input type="text" name="s_fullId[]" placeholder="octobre2026" style="width:110px"></td>',
        '<td><input type="text" name="s_icon[]" placeholder="🍂" style="width:48px"></td>',
        '<td><input type="text" name="s_saison[]" placeholder="Automne" style="width:100px"></td>',
        '<td><input type="text" name="s_saisonEn[]" placeholder="Autumn" style="width:100px"></td>',
        '<td><input type="text" name="s_dates[]" placeholder="Merc. 14 – Vend. 23 oct. 2026" style="width:220px"></td>',
        '<td><input type="text" name="s_datesEn[]" placeholder="Wed 14 – Fri 23 Oct 2026" style="width:210px"></td>',
        '<td><button type="button" class="button button-small stluth-del-row">–</button></td>'
      ].join('');
      var tbody = document.getElementById('stluth-sess-body');
      function bindDel(btn){ btn.addEventListener('click', function(){ this.closest('tr').remove(); }); }
      document.querySelectorAll('.stluth-del-row').forEach(bindDel);
      document.getElementById('stluth-add-sess').addEventListener('click', function(){
        var tr = document.createElement('tr');
        tr.innerHTML = tpl;
        tbody.appendChild(tr);
        bindDel(tr.querySelector('.stluth-del-row'));
      });
    })();
    </script>
    <?php
}

// (Paramètres intégrés dans la page Seed — pas de sous-menu séparé)

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

    wp_redirect( admin_url( 'admin.php?page=evd-seed&saved=1' ) );
    exit;
} );

// ── Admin : onglet Stages › Seed + Paramètres ───────────────────────────────

function evd_render_seed_page() {
    $sessions = get_option( 'stluth_sessions', [] );
    $tarif    = (int) get_option( 'stluth_tarif_retour', 80 );
    $tab      = sanitize_key( $_GET['tab'] ?? 'sessions' );
    if ( isset( $_GET['seeded'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Seed exécuté.</p></div>';
    if ( isset( $_GET['saved'] ) )  echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés.</p></div>';
    $page_url = admin_url( 'admin.php?page=evd-seed' );
    ?>
    <div class="wrap">
    <h1>Stages ewendaviau</h1>

    <nav class="nav-tab-wrapper" style="margin-bottom:1.5rem">
      <a href="<?php echo esc_url( $page_url . '&tab=sessions' ); ?>"
         class="nav-tab <?php echo $tab === 'sessions' ? 'nav-tab-active' : ''; ?>">Sessions</a>
      <a href="<?php echo esc_url( $page_url . '&tab=seed' ); ?>"
         class="nav-tab <?php echo $tab === 'seed' ? 'nav-tab-active' : ''; ?>">Seed</a>
      <a href="<?php echo esc_url( $page_url . '&tab=params' ); ?>"
         class="nav-tab <?php echo $tab === 'params' ? 'nav-tab-active' : ''; ?>">Paramètres</a>
    </nav>

    <?php if ( $tab === 'sessions' ) : ?>

      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="stluth_params_save">
        <input type="hidden" name="stluth_tarif_retour" value="<?php echo esc_attr( $tarif ); ?>">
        <?php wp_nonce_field( 'stluth_params_save' ); ?>

        <p class="description" style="margin-bottom:1rem">
          Sessions injectées dans le formulaire d'inscription (<code>window.STLUTH_ACTIVE_SESSIONS</code>).
          Laisser vide → affiche « Dates disponibles prochainement ».
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
        <p style="margin-top:1.5rem">
          <button type="submit" class="button button-primary">Enregistrer les sessions</button>
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

    <?php elseif ( $tab === 'params' ) : ?>

      <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="stluth_params_save">
        <?php wp_nonce_field( 'stluth_params_save' ); ?>
        <!-- Sessions vides — conserve les sessions existantes lors d'une sauvegarde params-only -->
        <?php foreach ( $sessions as $s ) : ?>
          <input type="hidden" name="s_fullId[]"   value="<?php echo esc_attr( $s['fullId']   ?? '' ); ?>">
          <input type="hidden" name="s_icon[]"     value="<?php echo esc_attr( $s['icon']     ?? '' ); ?>">
          <input type="hidden" name="s_saison[]"   value="<?php echo esc_attr( $s['saison']   ?? '' ); ?>">
          <input type="hidden" name="s_saisonEn[]" value="<?php echo esc_attr( $s['saisonEn'] ?? '' ); ?>">
          <input type="hidden" name="s_dates[]"    value="<?php echo esc_attr( $s['dates']    ?? '' ); ?>">
          <input type="hidden" name="s_datesEn[]"  value="<?php echo esc_attr( $s['datesEn']  ?? '' ); ?>">
        <?php endforeach; ?>

        <table class="form-table" style="max-width:580px"><tbody>
          <tr>
            <th><label for="stluth_tarif_retour">Retour atelier</label></th>
            <td>
              <input type="number" id="stluth_tarif_retour" name="stluth_tarif_retour"
                     value="<?php echo esc_attr( $tarif ); ?>" min="0" step="1" class="small-text"> €/jour
              <p class="description">
                Remplace <code>{{TARIF_RETOUR}}</code> dans toutes les pages au prochain seed.<br>
                Apparaît dans les stages, inscriptions et CGV (Art. 4).
              </p>
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

function evd_add_seed_admin_page() {
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
    wp_redirect( admin_url( 'admin.php?page=evd-seed&seeded=1' ) );
    exit;
} );
