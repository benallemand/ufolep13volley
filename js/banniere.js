Ext.onReady(function() {
    Ext.create('Ext.Img', {
        id : 'image_banniere',
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
    Ext.fly('image_banniere').setStyle('cursor', 'pointer');
});
