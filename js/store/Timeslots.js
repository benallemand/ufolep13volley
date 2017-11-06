Ext.define('Ufolep13Volley.store.Timeslots', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Timeslot',
        proxy: {
            type: 'ajax',
            url: 'ajax/getTimeslots.php'
        },
        autoLoad: true
    }
}));