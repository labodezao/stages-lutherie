/* Block editor registration — ewendaviau/html-libre
   save() → null : bloc dynamique, rendu côté PHP, jamais de validation Gutenberg. */
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
            var attrs = props.attributes;
            return el( 'div', { style: { padding: '12px', background: '#f8f8f8', border: '1px solid #ddd', borderRadius: '4px' } },
                el( 'p', { style: { fontWeight: 'bold', margin: '0 0 6px' } }, '🇫🇷 HTML Français' ),
                el( 'textarea', {
                    style:    { width: '100%', minHeight: '120px', fontFamily: 'monospace', fontSize: '12px', boxSizing: 'border-box' },
                    value:    attrs.html,
                    onChange: function ( e ) { props.setAttributes( { html: e.target.value } ); },
                } ),
                el( 'p', { style: { fontWeight: 'bold', margin: '10px 0 6px' } }, '🇬🇧 HTML English' ),
                el( 'textarea', {
                    style:    { width: '100%', minHeight: '120px', fontFamily: 'monospace', fontSize: '12px', boxSizing: 'border-box' },
                    value:    attrs.html_en,
                    onChange: function ( e ) { props.setAttributes( { html_en: e.target.value } ); },
                } )
            );
        },

        save: function () { return null; },
    } );

} )( window.wp.blocks, window.wp.element );
