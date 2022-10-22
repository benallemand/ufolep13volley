Ext.define('Ufolep13Volley.store.AdminDays', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Day',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/day/getDays',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true}
});