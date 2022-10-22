Ext.define('Ufolep13Volley.store.Teams', {
    extend: 'Ext.data.Store',
    alias: 'store.Teams',
    config: {
        model: 'Ufolep13Volley.model.Team',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/team/getTeams',
            reader: {
                type: 'json',
                root: 'results'
            },
            pageParam: undefined,
            startParam: undefined,
            limitParam: undefined
        },
        autoLoad: true}
});