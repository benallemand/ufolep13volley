Ext.define('Ufolep13Volley.store.Competitions', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Competition',
        proxy: {
            type: 'ajax',
            url: 'ajax/getCompetitions.php'
        },
        autoLoad: true
    }
});