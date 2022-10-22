Ext.define('Ufolep13Volley.store.RankTeams', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.RankTeam',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/team/getRankTeams',
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