Ext.define('Ufolep13Volley.store.ActAs', {
    extend: 'Ext.data.Store',
    storeId: 'ActAs',
    fields: ['id', 'login', 'email', 'is_admin', 'equipes'],
    proxy: {
        type: 'ajax',
        url: '/rest/action.php/usermanager/get_users_for_act_as',
        reader: {
            type: 'json'
        }
    },
    autoLoad: false
});
