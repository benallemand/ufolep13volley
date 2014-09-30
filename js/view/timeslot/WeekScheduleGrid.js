Ext.define('Ufolep13Volley.view.timeslot.WeekScheduleGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.weekschedulegrid',
    title: 'Planning de la semaine',
    autoScroll: true,
    store: 'WeekSchedule',
    columns: {
        items: [
            {
                header: 'Gymnase',
                dataIndex: 'gymnasium',
                width: 350
            },
            {
                header: 'Jour',
                dataIndex: 'dayOfWeek',
                width: 110
            },
            {
                header: 'Heure',
                dataIndex: 'startTime',
                width: 90
            },
            {
                header: 'Equipe',
                dataIndex: 'team',
                width: 450
            }
        ]
    }
});