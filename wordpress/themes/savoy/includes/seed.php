<?php
/**
 * Seed ewendaviau.com — intégré au thème, SQL pur via $wpdb.
 *
 * Chargé à la demande depuis functions.php :
 *   add_action('admin_post_evd_seed_run', function(){ require __DIR__.'/inc/seed.php'; evd_run_seed(); });
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
        case 'accueil':         $count = evd_seed_accueil();         break;
        case 'stages':          $count = evd_seed_stages();          break;
        case 'stages_en':       $count = evd_seed_stages_en();       break;
        case 'inscriptions':    $count = evd_seed_inscriptions();    break;
        case 'inscriptions_en': $count = evd_seed_inscriptions_en(); break;
        case 'programme':       $count = evd_seed_programme();       break;
        case 'contact':         $count = evd_seed_contact();         break;
        case 'cgv':             $count = evd_seed_cgv();             break;
        case 'all':
        default:
            $count += evd_seed_accueil();
            $count += evd_seed_stages();
            $count += evd_seed_stages_en();
            $count += evd_seed_inscriptions();
            $count += evd_seed_inscriptions_en();
            $count += evd_seed_programme();
            $count += evd_seed_contact();
            $count += evd_seed_cgv();
    }
    return $count;
}

// ── 1. ACCUEIL ────────────────────────────────────────────────────────────────

function evd_seed_accueil(): int {
    $fr = '
<section class="evd-hero">
  <h1>Ewen Daviau — Luthier</h1>
  <p>Fabricant d\'accordéons diatoniques sur mesure à Saint-Nazaire, France.</p>
</section>

<section class="evd-intro">
  <h2>Stages de lutherie · Accordéon diatonique</h2>
  <p>10 jours d\'immersion dans l\'art de la fabrication d\'un accordéon diatonique.<br>
  On apprend et on fait ensemble, à votre rythme.<br>
  Accessible à tous dès 13 ans, <strong>sans aucune expérience manuelle requise</strong>.</p>
  <p>
    <a href="/stages/" class="evd-btn">Découvrir les stages →</a>
    <a href="https://stages.ewendaviau.com" class="evd-btn evd-btn-sec" target="_blank">Inscription</a>
  </p>
</section>

<section class="evd-sessions">
  <h2>Sessions 2026</h2>
  <ul>
    <li>🌸 <strong>8–17 avril 2026</strong></li>
    <li>🍂 <strong>14–23 octobre 2026</strong></li>
  </ul>
  <p>Petits groupes · 4 à 6 personnes · Saint-Nazaire (44)</p>
</section>

<section class="evd-instruments">
  <h2>Instruments sur mesure</h2>
  <p>Accordéons diatoniques fabriqués à la commande : essence de bois, tonalité, boutons,
  couleurs de soufflets, grilles décoratives — plus de 100 combinaisons possibles.</p>
  <p><a href="/contact/" class="evd-btn">Me contacter</a></p>
</section>
';

    $en = '
<section class="evd-hero">
  <h1>Ewen Daviau — Luthier</h1>
  <p>Custom diatonic accordion maker in Saint-Nazaire, France.</p>
</section>

<section class="evd-intro">
  <h2>Lutherie Workshop · Diatonic Accordion</h2>
  <p>10 days of immersion into the art of building a diatonic accordion.<br>
  We learn and build together, at your own pace.<br>
  Open to everyone 13+, <strong>no prior experience required</strong>.</p>
  <p>
    <a href="/stages/?lang=en" class="evd-btn">Discover the workshops →</a>
    <a href="https://stages.ewendaviau.com?lang=en" class="evd-btn evd-btn-sec" target="_blank">Register</a>
  </p>
</section>

<section class="evd-sessions">
  <h2>2026 Sessions</h2>
  <ul>
    <li>🌸 <strong>April 8–17, 2026</strong></li>
    <li>🍂 <strong>October 14–23, 2026</strong></li>
  </ul>
  <p>Small groups · 4 to 6 participants · Saint-Nazaire, France</p>
</section>

<section class="evd-instruments">
  <h2>Custom Instruments</h2>
  <p>Diatonic accordions made to order: wood species, key, buttons,
  bellows colours, decorative grilles — over 100 possible combinations.</p>
  <p><a href="/contact/?lang=en" class="evd-btn">Contact me</a></p>
</section>
';

    evd_upsert_page( 'Accueil', 'accueil', evd_block( $fr, $en ) );
    return 1;
}

// ── 2. STAGES (Gutenberg depuis includes/) ───────────────────────────────────

function evd_read_include( string $filename ): string {
    $path = get_template_directory() . '/includes/' . $filename;
    if ( ! file_exists( $path ) ) {
        throw new \RuntimeException( "Fichier introuvable : $path" );
    }
    return file_get_contents( $path );
}

function evd_seed_stages(): int {
    $content = evd_read_include( 'stages-fr.html' );
    evd_upsert_page( 'Stage de lutherie — Accordéon diatonique', 'stages-lutherie', $content );
    return 1;
}

function evd_seed_stages_en(): int {
    $content = evd_read_include( 'stages-en.html' );
    evd_upsert_page( 'Diatonic Accordion Making Workshop', 'stages-accordion-workshop', $content );
    return 1;
}

function evd_seed_inscriptions(): int {
    $content = evd_read_include( 'inscriptions-fr.html' );
    evd_upsert_page( 'Inscription — Stage de lutherie', 'inscription-lutherie', $content );
    return 1;
}

function evd_seed_inscriptions_en(): int {
    $content = evd_read_include( 'inscriptions-en.html' );
    evd_upsert_page( 'Registration — Lutherie Workshop', 'registration-lutherie-workshop', $content );
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
  <li>✅ Préparation des pièces avant le stage</li>
  <li>✅ Matériaux, accessoires, consommables</li>
  <li>✅ Outillage professionnel</li>
  <li>✅ Sac + bretelles</li>
  <li>✅ Encadrement personnalisé tout au long du stage</li>
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
  <a href="https://stages.ewendaviau.com" class="evd-btn" target="_blank">📝 S\'inscrire</a>
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
  <li>✅ Parts preparation before the workshop</li>
  <li>✅ Materials, accessories, consumables</li>
  <li>✅ Professional tools</li>
  <li>✅ Gig bag + straps</li>
  <li>✅ Personal guidance throughout the workshop</li>
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
  <a href="https://stages.ewendaviau.com?lang=en" class="evd-btn" target="_blank">📝 Register</a>
  <a href="mailto:contact@ewendaviau.com" class="evd-btn evd-btn-sec">Contact us</a>
</p>
';

    // Legacy body — not called; kept as reference only.
    return 0;
}

// ── 3. PROGRAMME ─────────────────────────────────────────────────────────────

function evd_seed_programme(): int {
    $fr = '
<h1>Programme — 10 jours</h1>
<p><em>Programme indicatif. La progression dépend de chaque stagiaire et de chaque instrument.</em></p>
<ol>
  <li><strong>Jour 1</strong> — Accueil, visite atelier, choix des options, premiers assemblages</li>
  <li><strong>Jour 2</strong> — Structure bois : caisses MD/MG, ponçage, collage, équerrage</li>
  <li><strong>Jour 3</strong> — Claviers & boutons : axes, ressorts, toucher</li>
  <li><strong>Jour 4</strong> — Mécaniques MD/MG : assemblage complet, réglages hauteurs</li>
  <li><strong>Jour 5</strong> — Soufflets : fabrication, pose, ajustements, étanchéité</li>
  <li><strong>Jour 6</strong> — Grilles & finitions : fixation, décor, finitions bois</li>
  <li><strong>Jour 7</strong> — Anches : tri, installation, première mise en tension</li>
  <li><strong>Jour 8</strong> — Réglages fins : fuites d\'air, mécanique, alignement</li>
  <li><strong>Jour 9</strong> — Tests complets MD + MG, corrections personnalisées</li>
  <li><strong>Jour 10</strong> — Pratique musicale, photos, bilan de stage</li>
</ol>
<h2>Journée type</h2>
<ul>
  <li>9h30–12h30 : atelier fabrication</li>
  <li>12h30–14h00 : pause déjeuner</li>
  <li>14h00–17h30 : montage, réglages, accompagnement individuel</li>
  <li>Soirée : concerts, bals, échanges (selon planning)</li>
</ul>
<p><a href="/stages/">← Retour aux stages</a></p>
';

    $en = '
<h1>Program — 10 Days</h1>
<p><em>Indicative schedule. Progress varies according to each participant and instrument.</em></p>
<ol>
  <li><strong>Day 1</strong> — Welcome, workshop tour, option choices, first assemblies</li>
  <li><strong>Day 2</strong> — Wooden structure: RH/LH body, sanding, gluing, squaring</li>
  <li><strong>Day 3</strong> — Keyboards & buttons: axes, springs, touch adjustment</li>
  <li><strong>Day 4</strong> — RH/LH mechanics: full assembly, height adjustments</li>
  <li><strong>Day 5</strong> — Bellows: making, fitting, adjustments, airtightness</li>
  <li><strong>Day 6</strong> — Grilles & finishing: mounting, decoration, wood finishing</li>
  <li><strong>Day 7</strong> — Reeds: sorting, installation, initial tensioning</li>
  <li><strong>Day 8</strong> — Fine adjustments: air leaks, mechanics, key alignment</li>
  <li><strong>Day 9</strong> — Complete RH + LH tests, personalised corrections</li>
  <li><strong>Day 10</strong> — Musical practice, photos, group wrap-up</li>
</ol>
<h2>Typical daily schedule</h2>
<ul>
  <li>9:30–12:30: workshop building</li>
  <li>12:30–14:00: lunch break</li>
  <li>14:00–17:30: assembly, adjustments, individual guidance</li>
  <li>Evening: concerts, dances, friendly exchanges</li>
</ul>
<p><a href="/stages/?lang=en">← Back to workshops</a></p>
';

    $stages_id = (int) ( new WP_Query( [ 'post_type' => 'page', 'name' => 'stages', 'fields' => 'ids', 'posts_per_page' => 1 ] ) )->posts[0] ?? 0;
    evd_upsert_page( 'Programme', 'programme', evd_block( $fr, $en ), $stages_id );
    return 1;
}

// ── 4. CONTACT ────────────────────────────────────────────────────────────────

function evd_seed_contact(): int {
    $fr = '
<h1>Contact</h1>
<p>Pour toute question sur les stages, les instruments ou les tarifs :</p>
<ul>
  <li>✉️ <a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a></li>
  <li>🌐 <a href="https://stages.ewendaviau.com" target="_blank">stages.ewendaviau.com</a> — inscription en ligne</li>
  <li>🎥 <a href="https://www.youtube.com/watch?v=DREx2RqMXeU" target="_blank">Vidéo de présentation</a></li>
</ul>
<h2>Atelier</h2>
<p>📍 9 rue Fernand de Magellan<br>44600 Saint-Nazaire, France</p>
<p>Quartier calme, à environ 20 minutes à pied de la mer.</p>
';

    $en = '
<h1>Contact</h1>
<p>For any questions about workshops, instruments or pricing:</p>
<ul>
  <li>✉️ <a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a></li>
  <li>🌐 <a href="https://stages.ewendaviau.com?lang=en" target="_blank">stages.ewendaviau.com</a> — online registration</li>
  <li>🎥 <a href="https://www.youtube.com/watch?v=DREx2RqMXeU" target="_blank">Presentation video</a></li>
</ul>
<h2>Workshop</h2>
<p>📍 9 rue Fernand de Magellan<br>44600 Saint-Nazaire, France</p>
<p>Quiet neighbourhood, about 20 minutes walk from the sea.</p>
';

    evd_upsert_page( 'Contact', 'contact', evd_block( $fr, $en ) );
    return 1;
}

// ── 5. CGV ────────────────────────────────────────────────────────────────────

function evd_seed_cgv(): int {
    $fr = '
<h1>Conditions Générales de Vente</h1>
<p><em>Stages de lutherie accordéon diatonique — Ewen Daviau, Saint-Nazaire</em></p>

<h2>Article 1 — Objet</h2>
<p>Les présentes CGV s\'appliquent à toute inscription à un stage de lutherie organisé par Ewen Daviau.
Le stage est un <strong>parcours pédagogique d\'initiation à la lutherie</strong> : le stagiaire
apprend les gestes du métier sous la guidance du formateur. Il ne s\'agit pas d\'une prestation
commerciale de fabrication ou de livraison d\'instrument.</p>

<h2>Article 2 — Nature du stage</h2>
<p>Le stage de 10 jours constitue un cadre d\'apprentissage intensif. La progression et le niveau
d\'avancement dépendent du stagiaire (aptitudes, rythme, modèle choisi). Le formateur accompagne
chaque stagiaire au mieux de ses capacités tout au long du stage.</p>

<h2>Article 3 — Non-garantie de finalisation</h2>
<p><strong>Un seul stage de 10 jours ne garantit pas nécessairement la finalisation complète de l\'accordéon.</strong>
La lutherie est un artisanat de précision qui ne se chronomètre pas. Certaines étapes — réglages
des anches, ajustements mécaniques fins, finitions — nécessitent du temps et de la pratique.
Le formateur s\'engage à permettre au stagiaire d\'avancer au maximum sur son instrument.</p>

<h2>Article 4 — Session de retour</h2>
<p>Si l\'instrument n\'est pas finalisé à l\'issue du stage, le stagiaire peut revenir lors d\'une
session ultérieure. <strong>Tarif : 80 € / jour</strong> (accès atelier, outillage, consommables,
guidance). Jours à convenir selon disponibilités. Le stagiaire peut également terminer certaines
étapes à domicile, avec l\'appui du formateur par email.</p>

<h2>Article 5 — Inscription et acompte</h2>
<table>
  <thead><tr><th>Modèle</th><th>Acompte (40 %)</th></tr></thead>
  <tbody>
    <tr><td>21/8</td><td>900 €</td></tr>
    <tr><td>33/12</td><td>1 500 €</td></tr>
    <tr><td>33/18</td><td>1 900 €</td></tr>
    <tr><td>33/24</td><td>2 500 €</td></tr>
  </tbody>
</table>
<p>L\'acompte couvre les frais de préparation des pièces engagés avant le stage.
Il est <strong>non remboursable</strong> sauf annulation à l\'initiative du formateur.
Le solde est réglé au démarrage du stage.</p>

<h2>Article 6 — Annulation et report</h2>
<ul>
  <li><strong>Annulation par le stagiaire</strong> : acompte acquis. La place peut être reportée
  sur une session ultérieure ou cédée à un tiers, à convenir avec le formateur.</li>
  <li><strong>Annulation par le formateur</strong> (force majeure ou effectif insuffisant) :
  acompte intégralement remboursé ou place reportée.</li>
</ul>

<h2>Article 7 — Matériaux</h2>
<p>Le tarif inclut l\'ensemble des matériaux, pièces, consommables, outillage et accessoires (sac + bretelles).
En cas de casse ou d\'erreur irréparable nécessitant le remplacement d\'une pièce majeure, des frais
supplémentaires peuvent être facturés au coût réel, après accord du stagiaire.</p>

<h2>Article 8 — Utilisation du modèle</h2>
<p>Le modèle transmis est destiné à un <strong>usage personnel non commercial</strong>. Toute reproduction
à des fins de revente est interdite sans accord écrit du formateur.</p>

<h2>Article 9 — Responsabilité</h2>
<p>Le formateur ne saurait être tenu responsable du niveau d\'avancement de l\'instrument à l\'issue du stage,
ni des dommages résultant d\'une manipulation incorrecte du stagiaire.</p>

<h2>Article 10 — Données personnelles (RGPD)</h2>
<p>Les données collectées à l\'inscription sont utilisées uniquement pour l\'organisation du stage et ne sont
pas transmises à des tiers. Droits d\'accès, rectification et suppression :
<a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a>.</p>

<p><em>CGV en vigueur à compter du 1er juillet 2026.</em></p>
';

    $en = '
<h1>Terms and Conditions</h1>
<p><em>Diatonic accordion lutherie workshops — Ewen Daviau, Saint-Nazaire, France</em></p>

<h2>Article 1 — Purpose</h2>
<p>These Terms apply to any registration for a lutherie workshop run by Ewen Daviau.
The workshop is a <strong>pedagogical learning journey into lutherie</strong>: the participant
learns craft skills under the instructor\'s guidance. It is not a commercial instrument
manufacturing or delivery service.</p>

<h2>Article 2 — Nature of the workshop</h2>
<p>The 10-day workshop is an intensive learning framework. Progress depends on the participant
(skills, pace, chosen model). The instructor accompanies each participant throughout.</p>

<h2>Article 3 — No guarantee of completion</h2>
<p><strong>A single 10-day workshop does not necessarily guarantee the complete finishing of the accordion.</strong>
Lutherie is a precision craft that cannot be timed to a deadline. Some steps — reed adjustment,
fine mechanical tuning, finishing — require time and practice.
The instructor commits to enabling each participant to advance as far as possible.</p>

<h2>Article 4 — Return sessions</h2>
<p>If the instrument is not complete at the end of the workshop, the participant may return for a
subsequent session. <strong>Rate: €80 / day</strong> (workshop access, tools, consumables, guidance).
Days to be arranged with the instructor. The participant may also complete certain steps at home,
with the instructor\'s support by email.</p>

<h2>Article 5 — Registration and deposit</h2>
<table>
  <thead><tr><th>Model</th><th>Deposit (40%)</th></tr></thead>
  <tbody>
    <tr><td>21/8</td><td>€900</td></tr>
    <tr><td>33/12</td><td>€1,500</td></tr>
    <tr><td>33/18</td><td>€1,900</td></tr>
    <tr><td>33/24</td><td>€2,500</td></tr>
  </tbody>
</table>
<p>The deposit covers preparation costs incurred before the workshop. It is <strong>non-refundable</strong>
except in the event of cancellation by the instructor. The balance is paid at the start of the workshop.</p>

<h2>Article 6 — Cancellation and deferral</h2>
<ul>
  <li><strong>Cancellation by the participant</strong>: deposit is retained. The place may be deferred
  or transferred to another person, by agreement with the instructor.</li>
  <li><strong>Cancellation by the instructor</strong> (force majeure or insufficient numbers):
  full deposit refund or deferral offered.</li>
</ul>

<h2>Article 7 — Materials</h2>
<p>The price includes all materials, parts, consumables, tools and accessories (gig bag + straps).
In the event of irreparable damage, additional material costs may be charged at cost price,
with the participant\'s prior agreement.</p>

<h2>Article 8 — Use of the model</h2>
<p>The accordion model taught is for the participant\'s <strong>personal, non-commercial use</strong>.
Reproduction for resale is prohibited without written agreement from the instructor.</p>

<h2>Article 9 — Liability</h2>
<p>The instructor cannot be held liable for the level of completion of the instrument, nor for damage
resulting from incorrect handling by the participant.</p>

<h2>Article 10 — Personal data (GDPR)</h2>
<p>Data collected at registration is used solely to organise the workshop and is not shared with third
parties. Rights of access, rectification and deletion:
<a href="mailto:contact@ewendaviau.com">contact@ewendaviau.com</a>.</p>

<p><em>Terms in force from 1 July 2026.</em></p>
';

    evd_upsert_page( 'CGV — Conditions Générales de Vente', 'cgv', evd_block( $fr, $en ) );
    return 1;
}
