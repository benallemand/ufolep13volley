Ext.define('Ufolep13Volley.store.Clubs', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Club',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/club/get'
        },
        autoLoad: true
    }
});