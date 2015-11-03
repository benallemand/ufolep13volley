Ext.define('Ufolep13Volley.store.AdminMatches', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Match',
        proxy: {
            type: 'ajax',
            url: 'ajax/getMatches.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));