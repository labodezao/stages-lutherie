<?php
/**
 * Plugin Name:  Seed EwenDaviau
 * Description:  Seed les pages du site ewendaviau.com — bilingue FR/EN avec blocs dynamiques.
 * Version:      1.0.0
 * Author:       Ewen Daviau
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EVD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EVD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// ── 1. BLOC DYNAMIQUE ewendaviau/html-libre ──────────────────────────────────

add_action( 'init', function () {
    wp_register_script(
        'evd-block-editor',
        EVD_PLUGIN_URL . 'assets/block.js',
        [ 'wp-blocks', 'wp-element' ],
        '1.0.0'
    );

    register_block_type( 'ewendaviau/html-libre', [
        'attributes'      => [
            'html'    => [ 'type' => 'string', 'default' => '' ],
            'html_en' => [ 'type' => 'string', 'default' => '' ],
        ],
        'editor_script'   => 'evd-block-editor',
        'render_callback' => function ( $attrs ) {
            $fr = $attrs['html']    ?? '';
            $en = $attrs['html_en'] ?? '';
            return '<div class="evd-lang-fr">' . $fr . '</div>'
                 . '<div class="evd-lang-en">' . $en . '</div>';
        },
    ] );
} );

// ── 2. SYSTÈME BILINGUE ──────────────────────────────────────────────────────

add_action( 'init', function () {
    if ( isset( $_GET['lang'] ) ) {
        $lang = sanitize_key( $_GET['lang'] );
        if ( in_array( $lang, [ 'fr', 'en' ], true ) ) {
            setcookie( 'ewendaviau_lang', $lang, time() + YEAR_IN_SECONDS, '/' );
        }
    }
} );

function evd_lang(): string {
    return ( $_COOKIE['ewendaviau_lang'] ?? 'fr' ) === 'en' ? 'en' : 'fr';
}

// Script inline priorité 1 : applique lang-en sur <html> avant le rendu du body
add_action( 'wp_head', function () {
    ?>
<script>(function(){var c=document.cookie.match(/ewendaviau_lang=([^;]+)/);if(c&&c[1]==='en')document.documentElement.classList.add('lang-en');})();</script>
<style>
.evd-lang-en{display:none}
html.lang-en .evd-lang-fr{display:none}
html.lang-en .evd-lang-en{display:block}
</style>
    <?php
}, 1 );

// ── 3. MENU ADMIN ────────────────────────────────────────────────────────────

add_action( 'admin_menu', function () {
    add_management_page(
        'Seed EwenDaviau',
        'Seed EwenDaviau',
        'manage_options',
        'seed-ewendaviau',
        'evd_admin_page'
    );
} );

function evd_admin_page(): void {
    $nonce = wp_nonce_field( 'evd_seed', 'evd_nonce', true, false );
    $action_url = esc_url( admin_url( 'admin-post.php' ) );

    echo '<div class="wrap"><h1>🌱 Seed EwenDaviau</h1>';
    echo '<p>Crée ou met à jour les pages du site <strong>ewendaviau.com</strong> avec le contenu bilingue FR/EN.</p>';

    echo '<form method="post" action="' . $action_url . '">' . $nonce;
    echo '<input type="hidden" name="action" value="evd_seed_run">';
    echo '<table class="widefat" style="max-width:500px;margin-top:16px"><tbody>';

    $buttons = [
        'pages'       => '📄 Pages principales',
        'stages'      => '🎵 Page Stages détaillée',
        'programme'   => '📅 Programme jour par jour',
        'contact'     => '✉️ Page Contact',
        'all'         => '🚀 Tout seeder',
    ];

    foreach ( $buttons as $val => $label ) {
        echo '<tr><td style="padding:8px">';
        echo '<button class="button ' . ( $val === 'all' ? 'button-primary' : 'button-secondary' ) . '" name="seed" value="' . esc_attr( $val ) . '">' . esc_html( $label ) . '</button>';
        echo '</td></tr>';
    }

    echo '</tbody></table></form>';

    if ( isset( $_GET['seeded'] ) ) {
        echo '<div class="notice notice-success is-dismissible" style="margin-top:16px"><p>✅ Seed terminé — <strong>' . esc_html( $_GET['seeded'] ) . '</strong> page(s) créée(s) ou mise(s) à jour.</p></div>';
    }

    echo '<hr style="margin-top:24px"><h2>Tester le bilinguisme</h2>';
    echo '<p><a href="' . esc_url( home_url( '/?lang=fr' ) ) . '" target="_blank">→ Version FR</a> &nbsp; ';
    echo '<a href="' . esc_url( home_url( '/?lang=en' ) ) . '" target="_blank">→ Version EN</a></p>';
    echo '</div>';
}

// ── 4. HANDLER ADMIN POST ────────────────────────────────────────────────────

add_action( 'admin_post_evd_seed_run', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
    check_admin_referer( 'evd_seed', 'evd_nonce' );

    require_once EVD_PLUGIN_DIR . 'inc/seed.php';

    $seed = sanitize_key( $_POST['seed'] ?? 'all' );
    $count = 0;

    switch ( $seed ) {
        case 'pages':     $count = evd_seed_pages();     break;
        case 'stages':    $count = evd_seed_stages();    break;
        case 'programme': $count = evd_seed_programme(); break;
        case 'contact':   $count = evd_seed_contact();   break;
        case 'all':
        default:
            $count  = evd_seed_pages();
            $count += evd_seed_stages();
            $count += evd_seed_programme();
            $count += evd_seed_contact();
            break;
    }

    wp_redirect( admin_url( 'tools.php?page=seed-ewendaviau&seeded=' . $count ) );
    exit;
} );
