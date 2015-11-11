Ext.define('Ufolep13Volley.store.LimitDates', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.LimitDate',
        proxy: {
            type: 'ajax',
            url: 'ajax/getLimitDates.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));