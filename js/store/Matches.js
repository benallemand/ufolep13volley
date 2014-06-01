Ext.define('Ufolep13Volley.store.Matches', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Match',
    groupField: 'journee',
    proxy: {
        type: 'ajax',
        url: 'ajax/getMatches.php',
        extraParams: {
            competition: competition,
            division: division
        },
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: true
});