Ext.define('Ufolep13.store.Matches', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13.model.Match',
        grouper : {
            groupFn: function(record) {
                return record.get('competition') + ' - ' + record.get('division_journee');
            },
            sortProperty: 'competition'
        },
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