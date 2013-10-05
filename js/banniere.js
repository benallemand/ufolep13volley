Ext.onReady(function() {
    Ext.create('Ext.Img', {
        src: './images/bandeau_1000x146.png',
        renderTo: Ext.get('banniere'),
        listeners: {
            el: {
                click: function() {
                    window.open('.', '_self', false);
                }
            }
        }
    });
});
