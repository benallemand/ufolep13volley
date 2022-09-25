Ext.define('Ufolep13Volley.store.register', {
    extend: 'Ext.data.Store',
    alias: 'store.register',
    config: {
        model: 'Ufolep13Volley.model.register',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/get_register'
        },
        autoLoad: true
    }

});