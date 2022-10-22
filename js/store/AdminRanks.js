Ext.define('Ufolep13Volley.store.AdminRanks', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Rank',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/rank/getRanks',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
});