Ext.define('Ufolep13Volley.store.mobile.Matches', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.mobile.Match',
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