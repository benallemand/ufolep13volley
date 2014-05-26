Ext.define('Ufolep13.store.Matches', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13.model.Match',
        groupField : 'competition',
        proxy: {
            type: 'ajax',
            url: '../ajax/getLastResults.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    }
});