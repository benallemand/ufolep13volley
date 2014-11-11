Ext.define('Ufolep13Volley.view.site.Banner', {
    extend: 'Ext.Img',
    alias: 'widget.banner',
    src: './images/ufolep13volley.png',
    border: false,
    style: {
        cursor: 'pointer'
    },
    listeners: {
        el: {
            click: function() {
                window.open('.', '_self', false);
            }
        }
    }
});
