Ext.define('Ufolep13Volley.store.Friendships', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Friendships',
        proxy: {
            type: 'ajax',
            url: 'ajax/get_friendships.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});