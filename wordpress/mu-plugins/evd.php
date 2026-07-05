<?php
/**
 * Plugin Name: EVD — Bloc bilingue + Seed
 * Description: Bloc Gutenberg FR/EN, système de langue et seed SQL pour ewendaviau.com.
 *              La gestion des sessions, emails et inscriptions est dans inscription-api.php.
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

// ── Admin : page Seed + tarif retour ─────────────────────────────────────────
// Sessions, emails et capacités sont gérés dans inscription-api.php
// (Stages → Inscriptions dans l'admin WP).

function evd_render_seed_page(): void {
    $tarif = (int) get_option( 'stluth_tarif_retour', 80 );

    if ( isset( $_GET['seeded'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Seed exécuté.</p></div>';
    if ( isset( $_GET['saved'] ) )  echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés.</p></div>';
    ?>
    <div class="wrap">
    <h1>Seed ewendaviau.com</h1>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:2rem">
      <input type="hidden" name="action" value="evd_seed_run">
      <?php wp_nonce_field( 'evd_seed_run' ); ?>
      <p class="description" style="margin-bottom:.75rem">
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

    <hr>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5rem">
      <input type="hidden" name="action" value="evd_tarif_save">
      <?php wp_nonce_field( 'evd_tarif_save' ); ?>
      <h2 style="margin-top:0">Tarif retour atelier</h2>
      <table class="form-table" style="max-width:520px"><tbody>
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
      <p><button type="submit" class="button button-secondary">Enregistrer le tarif</button></p>
    </form>
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

add_action( 'admin_post_evd_tarif_save', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'evd_tarif_save' );
    update_option( 'stluth_tarif_retour', max( 0, (int) ( $_POST['stluth_tarif_retour'] ?? 80 ) ) );
    wp_redirect( admin_url( 'admin.php?page=evd-seed&saved=1' ) );
    exit;
} );

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
