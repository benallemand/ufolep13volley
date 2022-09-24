Ext.define('Ufolep13Volley.store.RankTeams', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.RankTeam',
        proxy: {
            type: 'ajax',
            url: 'ajax/getRankTeams.php',
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