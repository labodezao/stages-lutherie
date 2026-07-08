<?php
/**
 * Seed ewendaviau.com — intégré au thème, SQL pur via $wpdb.
 *
 * Chargé à la demande depuis functions.php :
 *   add_action('admin_post_evd_seed_run', function(){ require __DIR__.'/seed.php'; evd_run_seed(); });
 *
 * RÈGLES :
 *  - Jamais wp_insert_post() / wp_update_post() → corrompt le JSON via wp_unslash
 *  - Tout passe par $wpdb (INSERT … ON DUPLICATE KEY UPDATE, UPDATE)
 *  - evd_block() génère le commentaire Gutenberg JSON self-closing
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Helpers ───────────────────────────────────────────────────────────────────

function evd_block( string $html_fr, string $html_en = '' ): string {
    $attrs = json_encode(
        [ 'html' => $html_fr, 'html_en' => $html_en ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    return "<!-- wp:ewendaviau/html-libre $attrs /-->\n";
}

/**
 * Écriture du post_content en SQL direct.
 * N'utilise jamais wp_update_post() qui passerait par wp_unslash et corromprait le JSON.
 */
function evd_write( int $post_id, string $content ): void {
    global $wpdb;
    $wpdb->update(
        $wpdb->posts,
        [ 'post_content' => $content, 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ],
        [ 'ID' => $post_id ]
    );
    clean_post_cache( $post_id );
    $post_obj = get_post( $post_id );
    do_action( 'save_post', $post_id, $post_obj, true );
    do_action( 'litespeed_purge_post', $post_id );
    if ( function_exists( 'rocket_clean_post' ) ) rocket_clean_post( $post_id );
    if ( function_exists( 'wp_cache_post_change' ) ) wp_cache_post_change( $post_id );
    if ( function_exists( 'w3tc_flush_post' ) ) w3tc_flush_post( $post_id );
}

/**
 * Crée ou met à jour une page via INSERT … ON DUPLICATE KEY UPDATE.
 * Clé de déduplication : post_name (slug) + post_type = 'page'.
 */
function evd_upsert_page( string $title_fr, string $slug, string $content, int $parent = 0 ): int {
    global $wpdb;

    $existing_id = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_name = %s AND post_type = 'page' AND post_status != 'trash'
         LIMIT 1",
        $slug
    ) );

    $now     = current_time( 'mysql' );
    $now_gmt = current_time( 'mysql', true );

    if ( $existing_id ) {
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->posts}
             SET post_title = %s, post_status = 'publish', post_modified = %s, post_modified_gmt = %s, post_parent = %d
             WHERE ID = %d",
            $title_fr, $now, $now_gmt, $parent, $existing_id
        ) );
        evd_write( $existing_id, $content );
        return $existing_id;
    }

    $author_id = (int) $wpdb->get_var( "SELECT MIN(ID) FROM {$wpdb->users}" );

    $wpdb->query( $wpdb->prepare(
        "INSERT INTO {$wpdb->posts}
         (post_author, post_date, post_date_gmt, post_content, post_title,
          post_status, post_name, post_type, post_parent,
          post_modified, post_modified_gmt,
          comment_status, ping_status, to_ping, pinged, post_content_filtered, guid)
         VALUES (%d, %s, %s, %s, %s, 'publish', %s, 'page', %d, %s, %s,
                 'closed', 'closed', '', '', '', '')",
        $author_id, $now, $now_gmt, $content, $title_fr,
        $slug, $parent, $now, $now_gmt
    ) );

    $new_id = (int) $wpdb->insert_id;

    // guid = permalink (nécessite l'ID connu)
    $wpdb->update(
        $wpdb->posts,
        [ 'guid' => get_permalink( $new_id ) ?: site_url( '/?page_id=' . $new_id ) ],
        [ 'ID' => $new_id ]
    );
    clean_post_cache( $new_id );
    return $new_id;
}

// ── Dispatcher ────────────────────────────────────────────────────────────────

function evd_run_seed(): int {
    $seed  = sanitize_key( $_POST['seed'] ?? 'all' );
    $count = 0;
    switch ( $seed ) {
        case 'stages':          $count = evd_seed_stages();          break;
        case 'stages_en':       $count = evd_seed_stages_en();       break;
        case 'inscriptions':    $count = evd_seed_inscriptions();    break;
        case 'inscriptions_en': $count = evd_seed_inscriptions_en(); break;
        case 'cgv':             $count = evd_seed_cgv();             break;
        case 'all':
        default:
            $count += evd_seed_stages();
            $count += evd_seed_stages_en();
            $count += evd_seed_inscriptions();
            $count += evd_seed_inscriptions_en();
            $count += evd_seed_cgv();
    }
    return $count;
}

// ── 1. STAGES (Gutenberg depuis includes/) ───────────────────────────────────

function evd_read_include( string $filename ): string {
    $path = get_template_directory() . '/includes/' . $filename;
    if ( ! file_exists( $path ) ) {
        throw new \RuntimeException( "Fichier introuvable : $path" );
    }
    return file_get_contents( $path );
}

function evd_apply_params( string $content ): string {
    $tarif = (string) get_option( 'stluth_tarif_retour', 80 );
    return str_replace( '{{TARIF_RETOUR}}', $tarif, $content );
}

function evd_seed_stages(): int {
    evd_upsert_page( 'Stage de lutherie — Accordéon diatonique', 'stages-lutherie',
        evd_apply_params( evd_read_include( 'stages-fr.html' ) ) );
    return 1;
}

function evd_seed_stages_en(): int {
    evd_upsert_page( 'Diatonic Accordion Making Workshop', 'stages-accordion-workshop',
        evd_apply_params( evd_read_include( 'stages-en.html' ) ) );
    return 1;
}

function evd_seed_inscriptions(): int {
    evd_upsert_page( 'Inscription — Stage de lutherie', 'inscription-lutherie',
        evd_apply_params( evd_read_include( 'inscriptions-fr.html' ) ) );
    return 1;
}

function evd_seed_inscriptions_en(): int {
    evd_upsert_page( 'Registration — Lutherie Workshop', 'registration-lutherie-workshop',
        evd_apply_params( evd_read_include( 'inscriptions-en.html' ) ) );
    return 1;
}

// ── (old evd_seed_stages plain-HTML fallback removed) ────────────────────────

function _evd_seed_stages_legacy_unused(): int {
    $fr = '
<h1>Stage de lutherie — Accordéon diatonique 2026</h1>

<p class="evd-lead">10 jours d\'immersion dans l\'art du luthier, dans un cadre calme et convivial.
Ce stage est avant tout une expérience d\'apprentissage : vous découvrez les gestes, les matériaux
et les outils qui donnent vie à un accordéon diatonique.
<strong>Aucune expérience manuelle requise.</strong></p>

<p>L\'accent est mis sur le chemin, pas sur la destination. Chaque jour est une avancée : vous
comprenez, vous faites, vous progressez ensemble. La majorité des participants repartent avec leur
instrument — et quand ce n\'est pas le cas, des séances de retour permettent de le finaliser sans pression.</p>

<h2>Public visé</h2>
<ul>
  <li>Ouvert à tous publics dès 13 ans (mineurs avec tuteur majeur)</li>
  <li>Débutants bienvenus, profils confirmés acceptés</li>
  <li>Curieux de lutherie, amateurs de musique traditionnelle, passionnés d\'artisanat</li>
</ul>

<h2>Sessions & lieu</h2>
<ul>
  <li>🌸 <strong>8–17 avril 2026</strong></li>
  <li>🍂 <strong>14–23 octobre 2026</strong></li>
</ul>
<p>Durée : 10 jours consécutifs<br>
Lieu : Atelier, 9 rue Fernand de Magellan, Saint-Nazaire (44)<br>
Horaires indicatifs : 9h30–12h30 / 14h00–17h30<br>
Repas et hébergements non inclus</p>

<h2>Ce que vous apprenez et faites</h2>
<ul>
  <li>Comprendre la structure et le fonctionnement d\'un accordéon diatonique</li>
  <li>Travailler le bois : caisse de résonance, ponçage, collage, équerrage</li>
  <li>Assembler les soufflets et vérifier l\'étanchéité</li>
  <li>Monter et régler les mécaniques, claviers et ressorts</li>
  <li>Poser les grilles décoratives, réaliser les finitions bois</li>
  <li>Installer et trier les anches, effectuer les premiers réglages et tests MD/MG</li>
  <li>Prendre en main musicalement l\'instrument en cours de fabrication</li>
</ul>

<h2>Et si l\'instrument n\'est pas terminé au bout de 10 jours ?</h2>
<p>C\'est une possibilité tout à fait normale, qui dépend du modèle choisi, de votre rythme et de
votre expérience. Cela ne remet pas en question la qualité de votre parcours — chaque heure passée
dans l\'atelier est une heure apprise.</p>
<p>Des séances de retour sont proposées à <strong>80 € / jour d\'atelier</strong>, pour finaliser
l\'instrument à votre rythme. Certains reviennent 1 ou 2 jours, d\'autres davantage. L\'accordéon
sera le vôtre et il sera terminé — simplement en plusieurs étapes.</p>

<h2>Modèles et tarifs</h2>
<table>
  <thead><tr><th>Modèle</th><th>Tarif</th><th>Acompte 40 %</th></tr></thead>
  <tbody>
    <tr><td>21/8 basses</td><td>2 820 €</td><td>900 €</td></tr>
    <tr><td>33/12 basses</td><td>4 500 €</td><td>1 500 €</td></tr>
    <tr><td>33/18 basses</td><td>4 880 €</td><td>1 900 €</td></tr>
    <tr><td>33/24 basses</td><td>6 250 €</td><td>2 500 €</td></tr>
  </tbody>
</table>
<p><em>Option Anche a mano Blue Star : +15 % · Tous modèles en 1 voix MD par défaut.<br>
Upgrade 2 voix MD avec registres possible (nécessite 2 stages).</em></p>

<h2>Personnalisations</h2>
<ul>
  <li><strong>Essences</strong> : merisier, noyer, érable sycomore</li>
  <li><strong>Tonalité</strong> : Sol/Do, Ré/Sol, La/Ré · Accordage sec/demi-sec</li>
  <li><strong>Soufflets</strong> : noir, rouge, bleu, vert, orange, mixte · Coins standard/renforcés/métal brossé/perso</li>
  <li><strong>Grilles décoratives</strong> : plus de 100 modèles numérotés (choisir à l\'inscription)</li>
  <li><strong>Clavier</strong> : Heim, Milleret-Pignol, ou personnalisé (surcoût)</li>
  <li><strong>Boutons</strong> : champignon bois naturel, nacre noire ou blanche</li>
  <li><strong>Anches</strong> : tipo a mano, a mano (+)</li>
</ul>

<h2>Inclus dans le tarif</h2>
<ul>
  <li>Préparation des pièces avant le stage</li>
  <li>Matériaux, accessoires, consommables</li>
  <li>Outillage professionnel</li>
  <li>Sac + bretelles</li>
  <li>Encadrement personnalisé tout au long du stage</li>
</ul>
<p><strong>Non inclus :</strong> hébergement, repas, transport.</p>

<h2>Déroulé type d\'une journée</h2>
<ul>
  <li>9h30–12h30 : atelier fabrication</li>
  <li>12h30–14h00 : pause déjeuner</li>
  <li>14h00–17h30 : montage, réglages, accompagnement individuel</li>
  <li>Soirée : concerts, bals, échanges (selon les jours)</li>
</ul>

<h2>Conditions importantes</h2>
<ul>
  <li>Acompte non remboursable (travail préparatoire engagé) ; possible de reporter la session ou transférer la place</li>
  <li>Le modèle transmis est destiné à un usage personnel non commercial</li>
  <li>Instrument non fini en 10 jours : retour possible à 80 € / jour atelier</li>
</ul>
<p><a href="/cgv/">→ Lire les Conditions Générales de Vente complètes</a></p>

<p>
  <a href="https://stages.ewendaviau.com" class="evd-btn" target="_blank">S\'inscrire</a>
  <a href="mailto:contact@ewendaviau.com" class="evd-btn evd-btn-sec">Nous contacter</a>
</p>
';

    $en = '
<h1>Lutherie Workshop — Diatonic Accordion 2026</h1>

<p class="evd-lead">A 10-day learning journey into diatonic accordion lutherie, in a calm and friendly atmosphere.
We learn and build together, step by step — a creative and sensory experience.
<strong>No prior tooling experience required.</strong></p>

<p>The aim is to develop real hands-on skills and understand how an accordion is made. Most participants
advance significantly during the 10 days; if further work is needed, you can return at €80/day.</p>

<h2>Target Audience</h2>
<ul>
  <li>Open to all audiences from age 13 (minors with adult guardian)</li>
  <li>Beginners welcome, experienced profiles accepted</li>
  <li>Curious about lutherie, folk music lovers, craft enthusiasts</li>
</ul>

<h2>Sessions &amp; Location</h2>
<ul>
  <li>🌸 <strong>April 8–17, 2026</strong></li>
  <li>🍂 <strong>October 14–23, 2026</strong></li>
</ul>
<p>Duration: 10 consecutive days<br>
Location: Workshop, 9 rue Fernand de Magellan, Saint-Nazaire (44), France<br>
Typical hours: 9:30–12:30 / 14:00–17:30<br>
Meals and lodging not included</p>

<h2>What You\'ll Learn to Do</h2>
<ul>
  <li>Understand the structure and workings of a diatonic accordion</li>
  <li>Woodworking: resonance box, sanding, gluing, squaring</li>
  <li>Assemble the bellows and check airtightness</li>
  <li>Mount and adjust mechanics, keyboards and springs</li>
  <li>Install decorative grills, perform wood finishing</li>
  <li>Install and sort reeds, perform initial RH/LH adjustments and tests</li>
  <li>Basic musical handling of the instrument you built</li>
</ul>

<h2>What if the instrument is not finished in 10 days?</h2>
<p>This is entirely normal, depending on the chosen model, your pace and experience. It does not call into
question the quality of your journey — every hour spent in the workshop is an hour learned.</p>
<p>Return sessions are available at <strong>€80 / workshop day</strong> to complete your instrument at your
own pace. Some participants return for 1 or 2 days, others for more. Your accordion will be yours and it
will be finished — simply in several stages.</p>

<h2>Models and Pricing</h2>
<table>
  <thead><tr><th>Model</th><th>Price</th><th>Deposit 40%</th></tr></thead>
  <tbody>
    <tr><td>21/8 basses</td><td>€2,820</td><td>€900</td></tr>
    <tr><td>33/12 basses</td><td>€4,500</td><td>€1,500</td></tr>
    <tr><td>33/18 basses</td><td>€4,880</td><td>€1,900</td></tr>
    <tr><td>33/24 basses</td><td>€6,250</td><td>€2,500</td></tr>
  </tbody>
</table>
<p><em>Anche a mano Blue Star option: +15% · All models: 1 voice RH by default.<br>
2-voice RH upgrade with registers available (requires 2 workshop sessions).</em></p>

<h2>Customisation</h2>
<ul>
  <li><strong>Woods</strong>: cherry, walnut, maple sycamore</li>
  <li><strong>Keys</strong>: G/C, D/G, A/D · Dry/semi-dry tuning</li>
  <li><strong>Bellows</strong>: black, red, blue, green, orange, mixed · Standard/reinforced/brushed metal/custom corners</li>
  <li><strong>Decorative grills</strong>: over 100 numbered models (choose at registration)</li>
  <li><strong>Keyboard layout</strong>: Heim, Milleret-Pignol, or custom (extra cost)</li>
  <li><strong>Buttons</strong>: mushroom in natural wood, black or white nacre</li>
  <li><strong>Reeds</strong>: tipo a mano, a mano (+)</li>
</ul>

<h2>Included in the price</h2>
<ul>
  <li>Parts preparation before the workshop</li>
  <li>Materials, accessories, consumables</li>
  <li>Professional tools</li>
  <li>Gig bag + straps</li>
  <li>Personal guidance throughout the workshop</li>
</ul>
<p><strong>Not included:</strong> accommodation, meals, transport.</p>

<h2>Typical Day Schedule</h2>
<ul>
  <li>9:30–12:30: workshop building</li>
  <li>12:30–14:00: lunch break</li>
  <li>14:00–17:30: assembly, adjustments, individual guidance</li>
  <li>Evening: concerts, dances, exchanges (on selected days)</li>
</ul>

<h2>Important terms</h2>
<ul>
  <li>Non-refundable deposit (preparatory work already engaged); possible to move session or transfer place</li>
  <li>The model is for personal, non-commercial use only</li>
  <li>Instrument not finished in 10 days: return possible at €80 / workshop day</li>
</ul>
<p><a href="/cgv/?lang=en">→ Read the full Terms &amp; Conditions</a></p>

<p>
  <a href="https://stages.ewendaviau.com?lang=en" class="evd-btn" target="_blank">Register</a>
  <a href="mailto:contact@ewendaviau.com" class="evd-btn evd-btn-sec">Contact us</a>
</p>
';

    // Legacy body — not called; kept as reference only.
    return 0;
}

// ── 2. CGV ────────────────────────────────────────────────────────────────────

function evd_seed_cgv(): int {
    $tarif = (string) get_option( 'stluth_tarif_retour', 80 );
    $fr    = str_replace( '{{TARIF_RETOUR}}', $tarif, evd_read_include( 'cgv-fr.html' ) );
    $en    = str_replace( '{{TARIF_RETOUR}}', $tarif, evd_read_include( 'cgv-en.html' ) );
    evd_upsert_page( 'CGV — Conditions Générales de Vente', 'cgv', evd_block( $fr, $en ) );
    return 1;
}

function _evd_seed_cgv_legacy_unused(): int {

    $cgv_style = '
<style>
.evd-cgv{max-width:780px;margin:0 auto;padding:0 1rem 3rem;font-family:inherit;color:inherit}
.evd-cgv-header{border-bottom:2px solid #3E2723;padding-bottom:1.2rem;margin-bottom:2rem}
.evd-cgv-header h1{margin:0 0 .35rem;font-size:1.7rem;font-weight:700;color:#3E2723}
.evd-cgv-header p{margin:0;font-size:.9rem;color:#6b5c54}
.evd-cgv-toc{background:#faf8f6;border:1px solid #e0d5cc;border-radius:4px;padding:1rem 1.2rem;margin-bottom:2.2rem}
.evd-cgv-toc p{margin:0 0 .5rem;font-weight:600;font-size:.85rem;letter-spacing:.04em;text-transform:uppercase;color:#6b5c54}
.evd-cgv-toc ol{margin:0;padding-left:1.4rem;font-size:.88rem;line-height:2}
.evd-cgv-toc ol li a{color:#3E2723;text-decoration:none}
.evd-cgv-toc ol li a:hover{text-decoration:underline}
.evd-cgv article{margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid #ede7e0}
.evd-cgv article:last-of-type{border-bottom:none}
.evd-cgv article h2{font-size:1rem;font-weight:700;color:#3E2723;margin:0 0 .7rem;display:flex;align-items:baseline;gap:.6rem}
.evd-cgv article h2 .art-num{font-size:.75rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#9e8276;flex-shrink:0}
.evd-cgv article p,.evd-cgv article ul{font-size:.93rem;line-height:1.75;margin:.4rem 0}
.evd-cgv article ul{padding-left:1.3rem}
.evd-cgv-callout{background:#fdf3e7;border-left:3px solid #c8a96e;border-radius:0 4px 4px 0;padding:.9rem 1.1rem;margin:.6rem 0}
.evd-cgv-callout p{margin:0;font-size:.92rem}
.evd-cgv table{width:100%;border-collapse:collapse;font-size:.9rem;margin:.8rem 0}
.evd-cgv table th{background:#3E2723;color:#fff;padding:.55rem .9rem;text-align:left;font-weight:600;font-size:.82rem;letter-spacing:.03em}
.evd-cgv table td{padding:.5rem .9rem;border-bottom:1px solid #ede7e0}
.evd-cgv table tbody tr:last-child td{border-bottom:none}
.evd-cgv-footer{margin-top:2.5rem;padding-top:1.2rem;border-top:1px solid #ede7e0;font-size:.82rem;color:#9e8276;display:flex;justify-content:space-between;flex-wrap:wrap;gap:.5rem}
.evd-cgv-footer a{color:#3E2723}
</style>
';

    $fr = $cgv_style . '
<div class="evd-cgv">

  <header class="evd-cgv-header">
    <h1>Conditions Générales de Vente</h1>
    <p>Stages de lutherie accordéon diatonique &mdash; Ewen Daviau, Saint-Nazaire</p>
  </header>

  <nav class="evd-cgv-toc">
    <p>Sommaire</p>
    <ol>
      <li><a href="#art1">Objet</a></li>
      <li><a href="#art2">Nature du stage</a></li>
      <li><a href="#art3">Non-garantie de finalisation</a></li>
      <li><a href="#art4">Session de retour</a></li>
      <li><a href="#art5">Inscription et acompte</a></li>
      <li><a href="#art6">Annulation et report</a></li>
      <li><a href="#art7">Matériaux</a></li>
      <li><a href="#art8">Utilisation du modèle</a></li>
      <li><a href="#art9">Responsabilité</a></li>
      <li><a href="#art10">Données personnelles</a></li>
    </ol>
  </nav>

  <article id="art1">
    <h2><span class="art-num">Art. 1</span> Objet</h2>
    <p>Les présentes CGV s\'appliquent à toute inscription à un stage de lutherie organisé par Ewen Daviau.
    Le stage est un <strong>parcours pédagogique d\'initiation à la lutherie</strong> : le stagiaire
    apprend les gestes du métier sous la guidance du formateur. Il ne s\'agit pas d\'une prestation
    commerciale de fabrication ou de livraison d\'instrument.</p>
  </article>

  <article id="art2">
    <h2><span class="art-num">Art. 2</span> Nature du stage</h2>
    <p>Le stage de 10 jours constitue un cadre d\'apprentissage intensif. La progression et le niveau
    d\'avancement dépendent du stagiaire (aptitudes, rythme, modèle choisi). Le formateur accompagne
    chaque stagiaire au mieux de ses capacités tout au long du stage.</p>
  </article>

  <article id="art3">
    <h2><span class="art-num">Art. 3</span> Non-garantie de finalisation</h2>
    <div class="evd-cgv-callout">
      <p><strong>Un seul stage de 10 jours ne garantit pas nécessairement la finalisation complète de l\'accordéon.</strong>
      La lutherie est un artisanat de précision qui ne se chronomètre pas. Certaines étapes — réglages
      des anches, ajustements mécaniques fins, finitions — nécessitent du temps et de la pratique.</p>
    </div>
    <p>Le formateur s\'engage à permettre au stagiaire d\'avancer au maximum sur son instrument.</p>
  </article>

  <article id="art4">
    <h2><span class="art-num">Art. 4</span> Session de retour</h2>
    <p>Si l\'instrument n\'est pas finalisé à l\'issue du stage, le stagiaire peut revenir lors d\'une
    session ultérieure. <strong>Tarif : 80 € / jour</strong> (accès atelier, outillage, consommables,
    guidance). Jours à convenir selon disponibilités. Le stagiaire peut également terminer certaines
    étapes à domicile, avec l\'appui du formateur par email.</p>
  </article>

  <article id="art5">
    <h2><span class="art-num">Art. 5</span> Inscription et acompte</h2>
    <table>
      <thead><tr><th>Modèle</th><th>Tarif</th><th>Acompte (40 %)</th></tr></thead>
      <tbody>
        <tr><td>21/8 basses</td><td>2 820 €</td><td>900 €</td></tr>
        <tr><td>33/12 basses</td><td>4 500 €</td><td>1 500 €</td></tr>
        <tr><td>33/18 basses</td><td>4 880 €</td><td>1 900 €</td></tr>
        <tr><td>33/24 basses</td><td>6 250 €</td><td>2 500 €</td></tr>
      </tbody>
    </table>
    <p>L\'acompte couvre les frais de préparation des pièces engagés avant le stage.
    Il est <strong>non remboursable</strong> sauf annulation à l\'initiative du formateur.
    Le solde est réglé au démarrage du stage.</p>
  </article>

  <article id="art6">
    <h2><span class="art-num">Art. 6</span> Annulation et report</h2>
    <ul>
      <li><strong>Annulation par le stagiaire</strong> : l\'acompte reste acquis. La place peut être
      reportée sur une session ultérieure ou cédée à un tiers, à convenir avec le formateur.</li>
      <li><strong>Annulation par le formateur</strong> (force majeure ou effectif insuffisant) :
      l\'acompte est intégralement remboursé ou la place reportée.</li>
    </ul>
  </article>

  <article id="art7">
    <h2><span class="art-num">Art. 7</span> Matériaux</h2>
    <p>Le tarif inclut l\'ensemble des matériaux, pièces, consommables, outillage et accessoires (sac + bretelles).
    En cas de casse ou d\'erreur irréparable nécessitant le remplacement d\'une pièce majeure, des frais
    supplémentaires peuvent être facturés au coût réel, après accord du stagiaire.</p>
  </article>

  <article id="art8">
    <h2><span class="art-num">Art. 8</span> Utilisation du modèle</h2>
    <p>Le modèle transmis est destiné à un <strong>usage personnel non commercial</strong>. Toute reproduction
    à des fins de revente est interdite sans accord écrit du formateur.</p>
  </article>

  <article id="art9">
    <h2><span class="art-num">Art. 9</span> Responsabilité</h2>
    <p>Le formateur ne saurait être tenu responsable du niveau d\'avancement de l\'instrument à l\'issue du stage,
    ni des dommages résultant d\'une manipulation incorrecte du stagiaire.</p>
  </article>

  <article id="art10">
    <h2><span class="art-num">Art. 10</span> Données personnelles (RGPD)</h2>
    <p>Les données collectées à l\'inscription sont utilisées uniquement pour l\'organisation du stage et ne sont
    pas transmises à des tiers. Droits d\'accès, rectification et suppression :
    <a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a>.</p>
  </article>

  <footer class="evd-cgv-footer">
    <span>CGV en vigueur à compter du 1<sup>er</sup> juillet 2026</span>
    <a href="/stages-lutherie/">← Retour aux stages</a>
  </footer>

</div>
';

    $en = $cgv_style . '
<div class="evd-cgv">

  <header class="evd-cgv-header">
    <h1>Terms and Conditions</h1>
    <p>Diatonic accordion lutherie workshops &mdash; Ewen Daviau, Saint-Nazaire, France</p>
  </header>

  <nav class="evd-cgv-toc">
    <p>Contents</p>
    <ol>
      <li><a href="#art1">Purpose</a></li>
      <li><a href="#art2">Nature of the workshop</a></li>
      <li><a href="#art3">No guarantee of completion</a></li>
      <li><a href="#art4">Return sessions</a></li>
      <li><a href="#art5">Registration and deposit</a></li>
      <li><a href="#art6">Cancellation and deferral</a></li>
      <li><a href="#art7">Materials</a></li>
      <li><a href="#art8">Use of the model</a></li>
      <li><a href="#art9">Liability</a></li>
      <li><a href="#art10">Personal data</a></li>
    </ol>
  </nav>

  <article id="art1">
    <h2><span class="art-num">Art. 1</span> Purpose</h2>
    <p>These Terms apply to any registration for a lutherie workshop run by Ewen Daviau.
    The workshop is a <strong>pedagogical learning journey into lutherie</strong>: the participant
    learns craft skills under the instructor\'s guidance. It is not a commercial instrument
    manufacturing or delivery service.</p>
  </article>

  <article id="art2">
    <h2><span class="art-num">Art. 2</span> Nature of the workshop</h2>
    <p>The 10-day workshop is an intensive learning framework. Progress depends on the participant
    (skills, pace, chosen model). The instructor accompanies each participant throughout the workshop.</p>
  </article>

  <article id="art3">
    <h2><span class="art-num">Art. 3</span> No guarantee of completion</h2>
    <div class="evd-cgv-callout">
      <p><strong>A single 10-day workshop does not necessarily guarantee the complete finishing of the accordion.</strong>
      Lutherie is a precision craft that cannot be timed to a deadline. Some steps — reed adjustment,
      fine mechanical tuning, finishing — require time and practice.</p>
    </div>
    <p>The instructor commits to enabling each participant to advance as far as possible on their instrument.</p>
  </article>

  <article id="art4">
    <h2><span class="art-num">Art. 4</span> Return sessions</h2>
    <p>If the instrument is not complete at the end of the workshop, the participant may return for a
    subsequent session. <strong>Rate: €80 / day</strong> (workshop access, tools, consumables, guidance).
    Days to be arranged with the instructor. The participant may also complete certain steps at home,
    with the instructor\'s support by email.</p>
  </article>

  <article id="art5">
    <h2><span class="art-num">Art. 5</span> Registration and deposit</h2>
    <table>
      <thead><tr><th>Model</th><th>Price</th><th>Deposit (40%)</th></tr></thead>
      <tbody>
        <tr><td>21/8 basses</td><td>€2,820</td><td>€900</td></tr>
        <tr><td>33/12 basses</td><td>€4,500</td><td>€1,500</td></tr>
        <tr><td>33/18 basses</td><td>€4,880</td><td>€1,900</td></tr>
        <tr><td>33/24 basses</td><td>€6,250</td><td>€2,500</td></tr>
      </tbody>
    </table>
    <p>The deposit covers preparation costs incurred before the workshop. It is <strong>non-refundable</strong>
    except in the event of cancellation by the instructor. The balance is paid at the start of the workshop.</p>
  </article>

  <article id="art6">
    <h2><span class="art-num">Art. 6</span> Cancellation and deferral</h2>
    <ul>
      <li><strong>Cancellation by the participant</strong>: the deposit is retained. The place may be deferred
      to a later session or transferred to another person, by agreement with the instructor.</li>
      <li><strong>Cancellation by the instructor</strong> (force majeure or insufficient numbers):
      full deposit refund or deferral offered.</li>
    </ul>
  </article>

  <article id="art7">
    <h2><span class="art-num">Art. 7</span> Materials</h2>
    <p>The price includes all materials, parts, consumables, tools and accessories (gig bag + straps).
    In the event of irreparable damage requiring replacement of a major part, additional costs may be
    charged at cost price, with the participant\'s prior agreement.</p>
  </article>

  <article id="art8">
    <h2><span class="art-num">Art. 8</span> Use of the model</h2>
    <p>The accordion model taught is for the participant\'s <strong>personal, non-commercial use</strong>.
    Reproduction for resale is prohibited without written agreement from the instructor.</p>
  </article>

  <article id="art9">
    <h2><span class="art-num">Art. 9</span> Liability</h2>
    <p>The instructor cannot be held liable for the level of completion of the instrument at the end of
    the workshop, nor for damage resulting from incorrect handling by the participant.</p>
  </article>

  <article id="art10">
    <h2><span class="art-num">Art. 10</span> Personal data (GDPR)</h2>
    <p>Data collected at registration is used solely to organise the workshop and is not shared with third
    parties. Rights of access, rectification and deletion:
    <a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a>.</p>
  </article>

  <footer class="evd-cgv-footer">
    <span>Terms in force from 1 July 2026</span>
    <a href="/stages-accordion-workshop/">← Back to workshops</a>
  </footer>

</div>
';

    evd_upsert_page( 'CGV — Conditions Générales de Vente', 'cgv', evd_block( $fr, $en ) );
    return 1; // end legacy unused
}
