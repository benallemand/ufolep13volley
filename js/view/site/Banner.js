Ext.define('Ufolep13Volley.view.site.Banner', {
    extend: 'Ext.Img',
    alias: 'widget.banner',
    src: './images/bandeau_1000x146.png',
    width: 1000,
    height: 146,
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
