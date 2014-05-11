Ext.define('Ufolep13Volley.store.Teams', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Team',
    proxy: {
        type: 'ajax',
        url: 'ajax/getTeams.php',
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