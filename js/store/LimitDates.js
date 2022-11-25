Ext.define('Ufolep13Volley.store.LimitDates', {
    extend: 'Ext.data.Store',
    alias: 'store.LimitDates',
    config: {
        model: 'Ufolep13Volley.model.LimitDate',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/limitdate/getLimitDates',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
});