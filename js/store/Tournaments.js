Ext.define('Ufolep13Volley.store.Tournaments', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Tournament',
        proxy: {
            type: 'ajax',
            url: 'ajax/getTournaments.php'
        },
        sorters: 'libelle',
        autoLoad: true
    }
}));