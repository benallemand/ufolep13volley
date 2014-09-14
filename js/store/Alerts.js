Ext.define('Ufolep13Volley.store.Alerts', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Alert',
        proxy: {
            type: 'ajax',
            url: 'ajax/getAlerts.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));