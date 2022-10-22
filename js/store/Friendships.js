Ext.define('Ufolep13Volley.store.Friendships', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Friendships',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/competition/get_friendships',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});