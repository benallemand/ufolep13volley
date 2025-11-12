Ext.define('Ufolep13Volley.store.email', {
    extend: 'Ext.data.Store',
    alias: 'store.email',
    config: {
        model: 'Ufolep13Volley.model.email',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/emails/get'
        },
        autoLoad: true,
    }

});