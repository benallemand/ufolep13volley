Ext.define('Ufolep13Volley.store.mobile.LastResults', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.mobile.LastResult',
        proxy: {
            type: 'ajax',
            url: 'ajax/getLastResults.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});