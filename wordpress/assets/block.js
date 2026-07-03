/* ewendaviau/html-libre — bloc dynamique bilingue FR/EN
   save()→null : rendu 100 % PHP, jamais de validation Gutenberg.
   Bumper la version dans functions.php à chaque changement. */
( function ( blocks, element ) {
    var el = element.createElement;

    blocks.registerBlockType( 'ewendaviau/html-libre', {
        title:    'HTML Libre FR/EN',
        icon:     'translation',
        category: 'common',
        attributes: {
            html:    { type: 'string', default: '' },
            html_en: { type: 'string', default: '' },
        },
        edit: function ( props ) {
            var a = props.attributes;
            return el( 'div', { style: { padding: '12px', background: '#f8f8f8', border: '1px solid #ddd', borderRadius: '4px' } },
                el( 'p', { style: { fontWeight: 'bold', margin: '0 0 4px' } }, '🇫🇷 HTML Français' ),
                el( 'textarea', {
                    style: { width: '100%', minHeight: '100px', fontFamily: 'monospace', fontSize: '12px', boxSizing: 'border-box' },
                    value: a.html,
                    onChange: function ( e ) { props.setAttributes( { html: e.target.value } ); },
                } ),
                el( 'p', { style: { fontWeight: 'bold', margin: '10px 0 4px' } }, '🇬🇧 HTML English' ),
                el( 'textarea', {
                    style: { width: '100%', minHeight: '100px', fontFamily: 'monospace', fontSize: '12px', boxSizing: 'border-box' },
                    value: a.html_en,
                    onChange: function ( e ) { props.setAttributes( { html_en: e.target.value } ); },
                } )
            );
        },
        save: function () { return null; },
    } );
} )( window.wp.blocks, window.wp.element );
