Ext.define('Ufolep13Volley.store.Teams', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
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
        autoLoad: true}
}));