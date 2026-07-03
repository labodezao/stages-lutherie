<?php
/**
 * Extrait à coller dans functions.php du thème ewendaviau.com
 *
 * Fonctionnalités intégrées :
 *  1. Bloc Gutenberg dynamique bilingue  ewendaviau/html-libre
 *  2. Système de langue FR/EN (cookie ewendaviau_lang + classe CSS lang-en)
 *  3. Menu Admin "Outils > Seed ewendaviau" pour remplir/réinitialiser le contenu
 *
 * Toutes les pages sont créées/mises à jour via $wpdb SQL pur (jamais wp_insert_post
 * ni wp_update_post, qui corrompent le JSON Gutenberg via wp_unslash).
 */

// ── 1. Bloc Gutenberg dynamique bilingue ──────────────────────────────────────

add_action( 'init', function () {
    if ( ! function_exists( 'register_block_type' ) ) return;

    register_block_type( 'ewendaviau/html-libre', [
        'editor_script'   => 'evd-block',
        'render_callback' => function ( array $attrs ): string {
            $lang    = evd_lang();
            $html_fr = $attrs['html']    ?? '';
            $html_en = $attrs['html_en'] ?? '';
            return ( $lang === 'en' && $html_en !== '' ) ? $html_en : $html_fr;
        },
        'attributes'      => [
            'html'    => [ 'type' => 'string', 'default' => '' ],
            'html_en' => [ 'type' => 'string', 'default' => '' ],
        ],
    ] );

    wp_register_script(
        'evd-block',
        get_template_directory_uri() . '/assets/block.js',
        [ 'wp-blocks', 'wp-element' ],
        '1.0.4',   // ← Bumper à chaque changement de block.js
        false
    );
} );

// ── 2. Système de langue FR/EN ────────────────────────────────────────────────

/**
 * Retourne 'en' ou 'fr' selon le cookie / paramètre URL.
 */
function evd_lang(): string {
    if ( isset( $_GET['lang'] ) ) {
        $l = sanitize_key( $_GET['lang'] );
        if ( in_array( $l, [ 'fr', 'en' ], true ) ) {
            return $l;
        }
    }
    return ( $_COOKIE['ewendavidu_lang'] ?? 'fr' ) === 'en' ? 'en' : 'fr';
}

// Pose le cookie et ajoute la classe lang-* au <html> avant le reste du rendu.
add_action( 'wp_head', function () {
    $lang = evd_lang();
    if ( isset( $_GET['lang'] ) ) {
        setcookie( 'ewendavidu_lang', $lang, time() + 365 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
    }
    // Inline JS en priorité 1 : ajoute la classe avant que le body soit peint.
    echo '<script>(function(){var l=' . json_encode( $lang ) . ';document.documentElement.classList.add("lang-"+l);})();</script>' . "\n";
}, 1 );

// CSS de visibilité bilingue (ajouté dans <head> via wp_add_inline_style).
add_action( 'wp_enqueue_scripts', function () {
    wp_add_inline_style( 'evd-theme', '
        .evd-lang-en { display: none; }
        html.lang-en .evd-lang-fr { display: none; }
        html.lang-en .evd-lang-en { display: block; }
    ' );
} );

// ── 3. Menu Admin : Seed ewendaviau ───────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_management_page(
        'Seed ewendaviau',
        'Seed ewendaviau',
        'manage_options',
        'evd-seed',
        'evd_seed_page'
    );
} );

function evd_seed_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) return;

    $seeds = [
        'all'       => 'Tout le site (accueil + stages + programme + contact + CGV)',
        'accueil'   => 'Page Accueil',
        'stages'    => 'Page Stages',
        'programme' => 'Page Programme',
        'contact'   => 'Page Contact',
        'cgv'       => 'Page CGV',
    ];

    echo '<div class="wrap"><h1>Seed ewendaviau.com</h1>';
    echo '<p>Lance le seed SQL pur (<code>$wpdb</code>) pour créer ou écraser les pages du site.</p>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    echo '<input type="hidden" name="action" value="evd_seed_run">';
    wp_nonce_field( 'evd_seed_run' );
    echo '<table class="widefat" style="max-width:600px"><thead><tr><th>Seed</th><th>Description</th><th></th></tr></thead><tbody>';

    foreach ( $seeds as $key => $label ) {
        echo '<tr>';
        echo '<td><code>' . esc_html( $key ) . '</code></td>';
        echo '<td>' . esc_html( $label ) . '</td>';
        echo '<td><button type="submit" name="seed" value="' . esc_attr( $key ) . '" class="button button-primary">Lancer</button></td>';
        echo '</tr>';
    }

    echo '</tbody></table></form></div>';
}

add_action( 'admin_post_evd_seed_run', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'evd_seed_run' );

    require_once get_template_directory() . '/inc/seed.php';
    $count = evd_run_seed();

    wp_redirect( add_query_arg(
        [ 'page' => 'evd-seed', 'seeded' => $count ],
        admin_url( 'tools.php' )
    ) );
    exit;
} );
