Ext.define('Ufolep13Volley.view.site.Banner', {
    extend: 'Ext.Img',
    alias: 'widget.banner',
    src: './images/ufolep13volley.png',
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
