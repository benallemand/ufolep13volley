Ext.define('Ufolep13Volley.store.AdminDays', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Day',
        proxy: {
            type: 'ajax',
            url: 'ajax/getDays.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));