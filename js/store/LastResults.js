Ext.define('Ufolep13Volley.store.LastResults', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.LastResult',
    proxy: {
        type: 'ajax',
        url: 'ajax/getLastResults.php',
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: true
});