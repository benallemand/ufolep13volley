Ext.define('Ufolep13Volley.store.LastPosts', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.LastPost',
        proxy: {
            type: 'ajax',
            url: 'ajax/getLastPosts.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        filters: [
            function(item) {
                return item.get('pubdate') > Date.parse('01/01/2013');
            }
        ],
        autoLoad: true}
});