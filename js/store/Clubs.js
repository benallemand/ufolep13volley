Ext.define('Ufolep13Volley.store.Clubs', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Club',
    proxy: {
        type: 'ajax',
        url: 'ajax/clubs.php',
        reader: {
            type: 'json',
            root: 'results'
        },
        pageParam: undefined,
        startParam: undefined,
        limitParam: undefined
    },
    autoLoad: true
});