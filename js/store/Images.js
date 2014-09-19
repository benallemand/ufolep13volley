Ext.define('Ufolep13Volley.store.Images', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Image',
        proxy: {
            type: 'ajax',
            url: 'ajax/getVolleyballImages.php',
            reader: {
                type: 'json',
                root: 'photo'
            }
        },
        autoLoad: true}
}));