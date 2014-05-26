Ext.define('Ufolep13.store.Tournaments', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13.model.Tournament',
        proxy: {
            type: 'ajax',
            url: '../ajax/getTournaments.php'
        },
        sorters: 'libelle',
        autoLoad: true
    }

});