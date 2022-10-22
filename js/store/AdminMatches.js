Ext.define('Ufolep13Volley.store.AdminMatches', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Match',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/matchmgr/getMatches',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
});