Ext.define('Ufolep13Volley.store.AdminRanks', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Rank',
        proxy: {
            type: 'ajax',
            url: 'ajax/getRanks.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));