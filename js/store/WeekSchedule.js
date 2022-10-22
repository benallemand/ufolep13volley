Ext.define('Ufolep13Volley.store.WeekSchedule', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.WeekSchedule',
        groupField: 'gymnasium',
        proxy: {
            type: 'ajax',
            url: '/rest/action.php/timeslot/getWeekSchedule'
        },
        autoLoad: true
    }
});