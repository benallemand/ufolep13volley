Ext.define('Ufolep13Volley.store.AdminCalendarEvents', {
    extend: 'Ext.data.Store',
    alias: 'store.AdminCalendarEvents',
    config: {
        model: 'Ufolep13Volley.model.CalendarEvent',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/calendarevents/getAllCalendarEvents',
            reader: {
                type: 'json'
            }
        },
        autoLoad: true
    }
});
