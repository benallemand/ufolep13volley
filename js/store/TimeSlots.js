Ext.define('Ufolep13Volley.store.TimeSlots', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.TimeSlot',
        proxy: {
            type: 'ajax',
            url: 'ajax/getTimeSlots.php'
        },
        autoLoad: true
    }
}));