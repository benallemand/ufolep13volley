Ext.define('Ufolep13Volley.store.match', {
    extend: 'Ext.data.Store',
    alias: 'store.match',
    config: {
        model: 'Ufolep13Volley.model.Match',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/match'
        }
    }
});