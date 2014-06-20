Ext.define('Ufolep13Volley.store.MyPreferences', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.Preference',
    proxy: {
        type: 'ajax',
        url: 'ajax/getMyPreferences.php',
        reader: {
            type: 'json',
            root: 'results'
        }
    },
    autoLoad: false
});