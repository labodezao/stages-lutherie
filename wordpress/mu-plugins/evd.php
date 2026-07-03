<?php
/**
 * Plugin Name: EVD — Bloc bilingue + Seed
 * Description: Bloc Gutenberg FR/EN, système de langue et seed SQL pour ewendaviau.com
 * Version: 1.0
 *
 * Déploiement : copier ce fichier dans wp-content/mu-plugins/evd.php
 * WordPress le charge automatiquement — aucune activation, functions.php inchangé.
 *
 * Copier aussi assets/block.js et inc/seed.php dans le thème actif.
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

// ── Admin : Outils › Seed ewendaviau ─────────────────────────────────────────

add_action( 'admin_menu', fn() => add_management_page( 'Seed', 'Seed ewendaviau', 'manage_options', 'evd-seed', function () {
    echo '<div class="wrap"><h1>Seed ewendaviau.com</h1>'
       . '<form method="post" action="' . admin_url( 'admin-post.php' ) . '">'
       . '<input type="hidden" name="action" value="evd_seed_run">';
    wp_nonce_field( 'evd_seed_run' );
    echo '<select name="seed">'
       . '<option value="all">Tout le site</option>'
       . '<option value="accueil">Accueil</option>'
       . '<option value="stages">Stages</option>'
       . '<option value="programme">Programme</option>'
       . '<option value="contact">Contact</option>'
       . '<option value="cgv">CGV</option>'
       . '</select> '
       . '<button class="button button-primary">Lancer</button>'
       . '</form></div>';
} ) );

add_action( 'admin_post_evd_seed_run', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'evd_seed_run' );
    require_once get_template_directory() . '/includes/seed.php';
    evd_run_seed();
    wp_redirect( admin_url( 'tools.php?page=evd-seed&seeded=1' ) );
    exit;
} );
