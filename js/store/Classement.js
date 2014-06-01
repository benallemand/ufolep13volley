Ext.define('Ufolep13Volley.store.Classement', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Classement',
    proxy: {
        type: 'ajax',
        url: 'ajax/getClassement.php',
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