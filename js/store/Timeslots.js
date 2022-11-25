Ext.define('Ufolep13Volley.store.Timeslots', {
    extend: 'Ext.data.Store',
    alias: 'store.Timeslots',
    config: {
        model: 'Ufolep13Volley.model.Timeslot',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/timeslot/getTimeSlots'
        },
        autoLoad: true
    }
});