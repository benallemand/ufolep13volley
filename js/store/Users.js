Ext.define('Ufolep13Volley.store.Users', {
    extend: 'Ext.data.Store',
    alias: 'store.Users',
    config: {
        model: 'Ufolep13Volley.model.User',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/usermanager/getUsers'
        },
        autoLoad: true}
});