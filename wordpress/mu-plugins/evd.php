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

// Cookie set early (before any HTML output) so headers are never already sent.
add_action( 'template_redirect', function () {
    if ( isset( $_GET['lang'] ) )
        setcookie( 'evd_lang', evd_lang(), time() + 365 * DAY_IN_SECONDS, '/', '', is_ssl(), true );
} );

add_action( 'wp_head', function () {
    $l = evd_lang();
    echo '<script>(function(){document.documentElement.classList.add("lang-' . esc_js( $l ) . '")})();</script>' . "\n";
    echo '<style>.evd-lang-en{display:none}html.lang-en .evd-lang-fr{display:none}html.lang-en .evd-lang-en{display:block}</style>' . "\n";
}, 1 );

// ── Substitution {{TARIF_RETOUR}} au rendu (fallback si seed n'a pas remplacé) ─
add_filter( 'the_content', function ( $content ) {
    if ( strpos( $content, '{{TARIF_RETOUR}}' ) === false ) return $content;
    return str_replace( '{{TARIF_RETOUR}}', (string) get_option( 'stluth_tarif_retour', 80 ), $content );
} );

// ── Config aperçu accordéon ───────────────────────────────────────────────────

function evd_apercu_default_config(): array {
    return [
        'actif'  => false,
        'layers' => [
            [ 'key' => 'caisse',   'divId' => 'accCaisse',   'formField' => 'boisClavier',     'formType' => 'select',
              'label' => 'Bois clavier',  'label_en' => 'Keyboard wood',
              'clipPath' => 'polygon(5% 8%,95% 8%,95% 92%,5% 92%)',    'opacity' => 1.0,
              'options' => [
                [ 'slug' => 'noyer',    'label' => 'Noyer',    'label_en' => 'Walnut', 'color' => '#8B6F47', 'imageUrl' => '' ],
                [ 'slug' => 'erable',   'label' => 'Érable',   'label_en' => 'Maple',  'color' => '#C8A96E', 'imageUrl' => '' ],
                [ 'slug' => 'cerisier', 'label' => 'Cerisier', 'label_en' => 'Cherry', 'color' => '#8B4513', 'imageUrl' => '' ],
              ],
            ],
            [ 'key' => 'soufflet', 'divId' => 'accSoufflet', 'formField' => 'couleurSoufflet', 'formType' => 'radio',
              'label' => 'Soufflet',      'label_en' => 'Bellows',
              'clipPath' => 'polygon(30% 8%,55% 8%,55% 92%,30% 92%)',  'opacity' => 1.0,
              'options' => [
                [ 'slug' => 'bleu',   'label' => 'Bleu',   'label_en' => 'Blue',   'color' => '#2e6cb5', 'imageUrl' => '' ],
                [ 'slug' => 'rouge',  'label' => 'Rouge',  'label_en' => 'Red',    'color' => '#c0392b', 'imageUrl' => '' ],
                [ 'slug' => 'orange', 'label' => 'Orange', 'label_en' => 'Orange', 'color' => '#e67e22', 'imageUrl' => '' ],
                [ 'slug' => 'noir',   'label' => 'Noir',   'label_en' => 'Black',  'color' => '#222222', 'imageUrl' => '' ],
              ],
            ],
            [ 'key' => 'grille',   'divId' => 'accGrille',   'formField' => 'boisGrille',      'formType' => 'select',
              'label' => 'Bois grille',   'label_en' => 'Grille wood',
              'clipPath' => 'polygon(58% 12%,92% 12%,92% 42%,58% 42%)', 'opacity' => 1.0,
              'options' => [
                [ 'slug' => 'noyer',    'label' => 'Noyer',    'label_en' => 'Walnut', 'color' => '#A0845C', 'imageUrl' => '' ],
                [ 'slug' => 'erable',   'label' => 'Érable',   'label_en' => 'Maple',  'color' => '#C8A96E', 'imageUrl' => '' ],
                [ 'slug' => 'cerisier', 'label' => 'Cerisier', 'label_en' => 'Cherry', 'color' => '#8B4513', 'imageUrl' => '' ],
              ],
            ],
            [ 'key' => 'boutons',  'divId' => 'accBoutons',  'formField' => 'boutonsMD',       'formType' => 'select',
              'label' => 'Boutons',       'label_en' => 'Buttons',
              'clipPath' => 'polygon(62% 16%,88% 16%,88% 38%,62% 38%)', 'opacity' => 1.0,
              'options' => [
                [ 'slug' => 'nacrine-noire',   'label' => 'Nacrine noire',   'label_en' => 'Black nacre',  'color' => '#333333', 'imageUrl' => '' ],
                [ 'slug' => 'nacrine-blanche', 'label' => 'Nacrine blanche', 'label_en' => 'White nacre',  'color' => '#F5F0E8', 'imageUrl' => '' ],
                [ 'slug' => 'noyer',           'label' => 'Noyer',           'label_en' => 'Walnut',       'color' => '#6B4226', 'imageUrl' => '' ],
                [ 'slug' => 'erable',          'label' => 'Érable',          'label_en' => 'Maple',        'color' => '#C8A96E', 'imageUrl' => '' ],
              ],
            ],
            [ 'key' => 'sangles',  'divId' => 'accSangles',  'formField' => 'couleurSangles',  'formType' => 'select',
              'label' => 'Sangles',       'label_en' => 'Straps',
              'clipPath' => 'polygon(60% 3%,68% 3%,68% 97%,60% 97%)',  'opacity' => 0.75,
              'options' => [
                [ 'slug' => 'bleu',     'label' => 'Bleu',      'label_en' => 'Blue',            'color' => '#2e6cb5', 'imageUrl' => '' ],
                [ 'slug' => 'rouge',    'label' => 'Rouge',     'label_en' => 'Red',             'color' => '#c0392b', 'imageUrl' => '' ],
                [ 'slug' => 'cuir-nat', 'label' => 'Cuir Nat.', 'label_en' => 'Natural leather', 'color' => '#B5885A', 'imageUrl' => '' ],
                [ 'slug' => 'noir',     'label' => 'Noir',      'label_en' => 'Black',           'color' => '#222222', 'imageUrl' => '' ],
              ],
            ],
        ],
    ];
}

const EVD_APERCU_FALLBACK_COLOR = '#888888';

function evd_apercu_sanitize_hex_color( string $color, string $fallback = EVD_APERCU_FALLBACK_COLOR ): string {
    $sanitized = function_exists( 'sanitize_hex_color' ) ? sanitize_hex_color( $color ) : '';
    if ( ! $sanitized && preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) $sanitized = $color;
    return $sanitized ?: $fallback;
}

function evd_apercu_get_config(): array {
    $saved = get_option( 'stluth_apercu_config', '' );
    if ( $saved ) {
        $decoded = json_decode( $saved, true );
        if ( is_array( $decoded ) ) return $decoded;
    }
    $config          = evd_apercu_default_config();
    $config['actif'] = (bool) get_option( 'stluth_apercu_actif', false );
    $old_images      = json_decode( get_option( 'stluth_apercu_images', '{}' ), true ) ?: [];
    if ( $old_images ) {
        foreach ( $config['layers'] as &$layer ) {
            foreach ( $layer['options'] as &$opt ) {
                $k = $layer['key'] . '-' . $opt['slug'];
                if ( isset( $old_images[ $k ] ) ) $opt['imageUrl'] = $old_images[ $k ];
            }
        }
    }
    return $config;
}

/**
 * Normalize the saved preview config before injecting it into front-end/admin JS.
 *
 * This keeps the expected layer metadata from the repository defaults while
 * preserving user-editable values from the admin UI. It also strips malformed
 * strings/colors/URLs so a bad saved value cannot break JSON serialization.
 *
 * @param array $config Raw config loaded from WordPress options.
 * @return array Safe config for inline JSON consumption.
 */
function evd_apercu_normalize_front_config( array $config ): array {
    $defaults   = evd_apercu_default_config();
    $raw_layers = [];

    if ( isset( $config['layers'] ) && is_array( $config['layers'] ) ) {
        foreach ( $config['layers'] as $layer ) {
            if ( ! is_array( $layer ) ) continue;
            $key = sanitize_key( $layer['key'] ?? '' );
            if ( $key !== '' ) $raw_layers[ $key ] = $layer;
        }
    }

    $normalized = [
        'actif'  => ! empty( $config['actif'] ),
        'layers' => [],
    ];

    foreach ( $defaults['layers'] as $default_layer ) {
        $layer   = $raw_layers[ $default_layer['key'] ] ?? [];
        $options = [];

        if ( isset( $layer['options'] ) && is_array( $layer['options'] ) ) {
            foreach ( $layer['options'] as $opt ) {
                if ( ! is_array( $opt ) ) continue;
                $options[] = [
                    'slug'     => sanitize_key( $opt['slug'] ?? '' ),
                    'label'    => sanitize_text_field( $opt['label'] ?? '' ),
                    'label_en' => sanitize_text_field( $opt['label_en'] ?? '' ),
                    'color'    => evd_apercu_sanitize_hex_color( (string) ( $opt['color'] ?? '' ) ),
                    'imageUrl' => esc_url_raw( $opt['imageUrl'] ?? '' ),
                ];
            }
        }

        if ( ! $options ) $options = $default_layer['options'];

        $normalized['layers'][] = [
            'key'       => $default_layer['key'],
            'divId'     => $default_layer['divId'],
            'formField' => $default_layer['formField'],
            'formType'  => $default_layer['formType'],
            'label'     => sanitize_text_field( $layer['label'] ?? $default_layer['label'] ),
            'label_en'  => sanitize_text_field( $layer['label_en'] ?? $default_layer['label_en'] ),
            'clipPath'  => sanitize_text_field( $layer['clipPath'] ?? $default_layer['clipPath'] ),
            'opacity'   => max( 0.0, min( 1.0, (float) ( $layer['opacity'] ?? $default_layer['opacity'] ) ) ),
            'options'   => $options,
        ];
    }

    return $normalized;
}

function evd_apercu_json_for_script( array $config ): string {
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $json  = wp_json_encode( evd_apercu_normalize_front_config( $config ), $flags );
    if ( is_string( $json ) && $json !== '' ) return $json;

    $fallback = wp_json_encode( evd_apercu_default_config(), $flags );
    if ( is_string( $fallback ) && $fallback !== '' ) return $fallback;

    return '{"actif":false,"layers":[]}';
}

// ── Injection config aperçu via wp_head ───────────────────────────────────────
add_action( 'wp_head', function () {
    $config_json = evd_apercu_json_for_script( evd_apercu_get_config() );
    echo '<script id="stluth-apercu-config" type="application/json">' . $config_json . '</script>' . "\n";
}, 2 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( strpos( $hook, 'evd-seed' ) !== false ) wp_enqueue_media();
} );

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

    <hr>
    <?php evd_render_apercu_admin(); ?>
    </div>
    <?php
}

function evd_render_apercu_admin(): void {
    $config = evd_apercu_get_config();
    ?>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="evd-apercu-form" style="margin-top:1.5rem">
      <input type="hidden" name="action" value="evd_apercu_save">
      <input type="hidden" name="apercu_config_json" id="apercu-config-json">
      <?php wp_nonce_field( 'evd_apercu_save' ); ?>

      <h2 style="margin-top:0">Aperçu accordéon</h2>

      <table class="form-table" style="max-width:520px"><tbody>
        <tr>
          <th>Activation</th>
          <td>
            <label>
              <input type="checkbox" id="evd-apercu-actif" value="1"<?php echo $config['actif'] ? ' checked' : ''; ?>>
              Afficher la section « Aperçu » dans le formulaire d'inscription
            </label>
            <p class="description">Si décoché, la section est masquée côté public (sans re-seed).</p>
          </td>
        </tr>
      </tbody></table>

      <h3>Aperçu composite</h3>
      <p class="description" style="margin-bottom:.75rem">Les calques se superposent dans l'ordre affiché. Choisissez une option par calque pour prévisualiser.</p>
      <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;margin-bottom:1.5rem">
        <div id="evd-compositor" style="position:relative;width:300px;height:225px;background:#f5f0e8;border-radius:8px;overflow:hidden;flex-shrink:0;border:1px solid #ccc"></div>
        <div id="evd-comp-selects" style="display:flex;flex-direction:column;gap:8px;padding-top:4px"></div>
      </div>

      <h3>Calques &amp; options</h3>
      <p class="description" style="margin-bottom:.75rem">
        <strong>Clip-path</strong> : format CSS <code>polygon(x% y%, …)</code> — définit la forme ET la position du calque dans le cadre 300×225 px.<br>
        <strong>Slug</strong> : utilisé dans le nom de fichier image (ex: <code>noyer</code> → <code>caisse-noyer.png</code>). La valeur du champ de formulaire est toujours l'étiquette FR.
      </p>
      <div id="evd-layers-editor"></div>

      <p style="margin-top:1.5rem">
        <button type="submit" class="button button-primary">Enregistrer l'aperçu</button>
      </p>
    </form>

    <script>
    jQuery(function($){
      var cfg = <?php echo evd_apercu_json_for_script( $config ); ?>;

      /* ── Compositor ────────────────────────────────── */
      function buildCompositor() {
        var $c = $('#evd-compositor').empty();
        var $s = $('#evd-comp-selects').empty();
        cfg.layers.forEach(function(layer, li) {
          $('<div>').css({position:'absolute',top:0,left:0,width:'100%',height:'100%','background-size':'cover','background-position':'center'}).attr('id','cmp-'+li).appendTo($c);
          var $row = $('<div>').css({display:'flex',alignItems:'center',gap:'6px'});
          $('<span>').css({fontSize:'12px',minWidth:'90px'}).text(layer.label).appendTo($row);
          var $sel = $('<select>').css({fontSize:'12px'}).attr('data-cmp-li', li);
          (layer.options||[]).forEach(function(opt,oi){ $('<option>').val(oi).text(opt.label).appendTo($sel); });
          $sel.on('change', function(){ paintLayer(li, parseInt($(this).val())); }).appendTo($row);
          $row.appendTo($s);
          paintLayer(li, 0);
        });
      }
      function paintLayer(li, oi) {
        var layer = cfg.layers[li]; if (!layer) return;
        var opt = layer.options[oi] || {};
        $('#cmp-'+li).css({'clip-path':layer.clipPath||'', opacity:layer.opacity!=null?layer.opacity:1, 'background-color':opt.color||'transparent', 'background-image':opt.imageUrl?'url('+opt.imageUrl+')':'none'});
      }

      /* ── Layer editor ──────────────────────────────── */
      function buildEditor() {
        var $ed = $('#evd-layers-editor').empty();
        cfg.layers.forEach(function(layer, li) { $ed.append(layerCard(layer, li)); });
      }
      function layerCard(layer, li) {
        var $card = $('<div>').css({border:'1px solid #ddd',borderRadius:'6px',padding:'14px 14px 10px',marginBottom:'14px',background:'#f9f9f9'});
        var $hdr = $('<div>').css({display:'flex',flexWrap:'wrap',gap:'10px',alignItems:'center',marginBottom:'10px'});
        $('<strong>').css({minWidth:'100px'}).text('▸ '+layer.label).appendTo($hdr);
        $hdr.append(lbl('Clip-path', fld('text','260px',layer.clipPath,li,null,'clipPath','font-family:monospace;font-size:11px')));
        $hdr.append(lbl('Opacité', fld('number','60px',layer.opacity,li,null,'opacity','')));
        $card.append($hdr);
        var $tbl = $('<table>').css({width:'100%',borderCollapse:'collapse',fontSize:'12px'}).append(
          '<thead><tr style="color:#666"><th style="padding:3px 6px;text-align:left">Étiquette FR</th><th style="padding:3px 6px;text-align:left">EN</th><th style="padding:3px 6px;text-align:left">Slug</th><th style="padding:3px 6px;text-align:left">Couleur</th><th style="padding:3px 6px;text-align:left">Image</th><th></th></tr></thead>'
        );
        var $tbody = $('<tbody>');
        (layer.options||[]).forEach(function(opt,oi){ $tbody.append(optRow(opt,li,oi)); });
        $tbl.append($tbody);
        $card.append($tbl);
        $('<button type="button" class="button" style="margin-top:8px;font-size:12px">+ Option</button>').on('click',function(){
          var o={slug:'',label:'',label_en:'',color:'#888888',imageUrl:''};
          layer.options.push(o);
          $tbody.append(optRow(o, li, layer.options.length-1));
          buildCompositor();
        }).appendTo($card);
        return $card;
      }
      function optRow(opt, li, oi) {
        var fid = 'aimg-'+li+'-'+oi;
        var $tr = $('<tr>').attr({'data-li':li,'data-oi':oi}).css('border-top','1px solid #eee');
        $tr.append(td(inp('text','110px',opt.label,        function(v){ cfg.layers[li].options[oi].label=v;    buildCompositor(); })));
        $tr.append(td(inp('text','90px', opt.label_en,    function(v){ cfg.layers[li].options[oi].label_en=v; })));
        $tr.append(td(inp('text','70px', opt.slug,        function(v){ cfg.layers[li].options[oi].slug=v; })));
        var $col = $('<input type="color">').css({width:'38px',height:'28px',padding:'1px',border:'1px solid #ccc'}).val(opt.color||'#888888');
        $col.on('input',function(){ cfg.layers[li].options[oi].color=this.value; buildCompositor(); });
        $tr.append(td($col));
        var $url = $('<input type="text">').css({width:'150px',fontSize:'11px'}).val(opt.imageUrl||'').attr('id',fid);
        $url.on('input',function(){ cfg.layers[li].options[oi].imageUrl=this.value; buildCompositor(); });
        var $pick = $('<button type="button" class="button evd-mpick" style="font-size:11px">Choisir</button>').data('li',li).data('oi',oi).data('fid',fid);
        var $thumb = $('<img>').attr({src:opt.imageUrl||''}).css({height:'28px',width:'auto',border:'1px solid #ddd',borderRadius:'2px',verticalAlign:'middle',display:opt.imageUrl?'':'none'}).attr('id','prev-'+fid);
        $tr.append($('<td style="padding:3px 6px">').append($('<div style="display:flex;align-items:center;gap:4px;flex-wrap:wrap">').append($url,$pick,$thumb)));
        $('<button type="button" class="button" style="font-size:11px;color:#a00;padding:2px 6px">✕</button>').on('click',function(){
          cfg.layers[li].options.splice(oi,1); buildEditor(); buildCompositor();
        }).wrap('<td style="padding:3px 6px">').parent().appendTo($tr);
        return $tr;
      }
      function lbl(t,el){ return $('<label>').css({fontSize:'12px'}).text(t+' ').append(el); }
      function fld(type,w,val,li,oi,f,xs){
        var $i=$('<input>').attr({type:type,value:val!=null?val:''}).css({width:w}).attr('data-li',li).attr('data-f',f);
        if(xs) $i.attr('style',($i.attr('style')||'')+';'+xs);
        return $i;
      }
      function inp(type,w,val,cb){ return $('<input>').attr({type:type,value:val||''}).css({width:w}).on('input',function(){ cb(this.value); }); }
      function td(c){ return $('<td style="padding:3px 6px">').append(c); }

      /* ── Live update for layer-level fields ─────────── */
      $(document).on('input change','[data-f]',function(){
        var li=parseInt($(this).attr('data-li')), f=$(this).attr('data-f'), v=$(this).val();
        if(f==='opacity') v=parseFloat(v)||1;
        cfg.layers[li][f]=v; buildCompositor();
      });

      /* ── Media picker ───────────────────────────────── */
      $(document).on('click','.evd-mpick',function(){
        var li=$(this).data('li'), oi=$(this).data('oi'), fid=$(this).data('fid');
        var frame=wp.media({title:'Sélectionner image aperçu',button:{text:'Utiliser'},multiple:false,library:{type:'image'}});
        frame.on('select',function(){
          var url=frame.state().get('selection').first().toJSON().url;
          cfg.layers[li].options[oi].imageUrl=url;
          $('#'+fid).val(url);
          $('#prev-'+fid).attr('src',url).show();
          buildCompositor();
        });
        frame.open();
      });

      /* ── Submit ─────────────────────────────────────── */
      $('#evd-apercu-form').on('submit',function(){
        cfg.actif=$('#evd-apercu-actif').is(':checked');
        $('#apercu-config-json').val(JSON.stringify(cfg));
      });

      buildCompositor(); buildEditor();
    });
    </script>
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

add_action( 'admin_post_evd_apercu_save', function () {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    check_admin_referer( 'evd_apercu_save' );
    $raw    = wp_unslash( $_POST['apercu_config_json'] ?? '{}' );
    $config = json_decode( $raw, true );
    if ( ! is_array( $config ) ) wp_die( 'Invalid config JSON', 400 );
    $config['actif'] = ! empty( $config['actif'] );
    foreach ( $config['layers'] as &$layer ) {
        $layer['key']      = sanitize_key( $layer['key'] ?? '' );
        $layer['clipPath'] = sanitize_text_field( $layer['clipPath'] ?? '' );
        $layer['opacity']  = max( 0.0, min( 1.0, (float) ( $layer['opacity'] ?? 1 ) ) );
        foreach ( $layer['options'] as &$opt ) {
            $opt['slug']     = sanitize_key( $opt['slug'] ?? '' );
            $opt['label']    = sanitize_text_field( $opt['label'] ?? '' );
            $opt['label_en'] = sanitize_text_field( $opt['label_en'] ?? '' );
            $opt['color']    = sanitize_hex_color( $opt['color'] ?? '' ) ?: '#888888';
            $opt['imageUrl'] = esc_url_raw( $opt['imageUrl'] ?? '' );
        }
    }
    update_option( 'stluth_apercu_config', wp_json_encode( $config ) );
    update_option( 'stluth_apercu_actif', $config['actif'] ? 1 : 0 );
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
