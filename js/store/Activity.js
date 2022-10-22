Ext.define('Ufolep13Volley.store.Activity', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Activity',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/activity/getActivity',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
});