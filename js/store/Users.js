Ext.define('Ufolep13Volley.store.Users', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.User',
    proxy: {
        type: 'ajax',
        url: 'ajax/getUsers.php'
    },
    autoLoad: true
});