Ext.define('Ufolep13Mobile.store.Tournaments', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Mobile.model.Tournament',
        proxy: {
            type: 'ajax',
            url: '../ajax/getTournaments.php'
        },
        sorters: 'libelle',
        autoLoad: true
    }

});