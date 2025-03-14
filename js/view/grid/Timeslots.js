Ext.define('Ufolep13Volley.view.grid.Timeslots', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.timeslots_grid',
    title: 'Créneaux',
    store: {type: 'Timeslots'},
    columns: {
        items: [
            {
                header: 'Equipe',
                dataIndex: 'team_full_name',
                flex: 1
            },
            {
                header: 'Gymnase',
                dataIndex: 'gymnasium_full_name',
                flex: 1
            },
            {
                header: 'Jour de la semaine',
                dataIndex: 'jour',
                flex: 1
            },
            {
                header: 'Heure',
                dataIndex: 'heure',
                flex: 1
            },
            {
                header: 'Contrainte horaire ?',
                dataIndex: 'has_time_constraint',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
                        return false;
                    }
                }
            },
            {
                header: "Priorité d'utilisation",
                dataIndex: 'usage_priority',
                flex: 1
            }
        ]
    }
});