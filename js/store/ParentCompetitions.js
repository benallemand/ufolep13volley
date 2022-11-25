Ext.define('Ufolep13Volley.store.ParentCompetitions', {
    extend: 'Ext.data.Store',
    alias: 'store.ParentCompetitions',
    config: {
        model: 'Ufolep13Volley.model.Competition',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/competition/getCompetitions'
        },
        autoLoad: true
    }
});