Ext.define('Ufolep13Volley.store.Timeslots', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Timeslot',
        proxy: {
            type: 'ajax',
            url: 'ajax/getTimeSlots.php'
        },
        autoLoad: true
    }
});