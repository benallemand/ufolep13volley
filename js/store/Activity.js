Ext.define('Ufolep13Volley.store.Activity', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Activity',
        proxy: {
            type: 'ajax',
            url: 'ajax/getActivity.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
}));