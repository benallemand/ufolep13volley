Ext.define('Ufolep13Volley.store.rank_for_cup', {
    extend: 'Ext.data.Store',
    alias: 'store.rank_for_cup',
    config: {
        model: 'Ufolep13Volley.model.rank_for_cup',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/rank/sort_cup_rank',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: false
    }
});