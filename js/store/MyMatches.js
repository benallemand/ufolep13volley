Ext.define('Ufolep13Volley.store.MyMatches', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Match',
    groupField: 'libelle_competition',
    proxy: {
        type: 'ajax',
        url: 'ajax/getMesMatches.php',
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: true
});