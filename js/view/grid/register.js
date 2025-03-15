Ext.define('Ufolep13Volley.view.grid.register', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.grid_register',
    title: 'Equipes inscrites',
    features: [
        {
            ftype: 'grouping',
            groupHeaderTpl: '{name}'
        }
    ],
    store:
        {
            type: 'register',
        },
    selModel: 'rowmodel',
    columns: [
        {
            header: "Date d'inscription",
            dataIndex: 'creation_date',
            xtype: 'datecolumn',
            format: 'd/m/Y H:i:s',
            hidden: false,
            width: 200,
        },
        {
            header: 'Equipe',
            defaults: {
                width: 200,
            },
            columns: [
                {header: 'Compétition', dataIndex: 'competition',},
                {header: 'Club', dataIndex: 'club',},
                {header: "Nom d'équipe", dataIndex: 'new_team_name',},
                {header: 'Ancien nom', dataIndex: 'old_team',},
                {header: 'Division', dataIndex: 'division',},
                {header: 'Rang de départ', dataIndex: 'rank_start',},
            ],
        },
        {header: 'Remarques', dataIndex: 'remarks', width: 200, cellWrap: true,},
        {header: 'Responsable', dataIndex: 'leader', width: 200,},
        {
            header: 'Créneaux(x)',
            defaults: {
                width: 200,
            },
            columns: [
                {header: 'Gymnase 1', dataIndex: 'court_1', },
                {header: 'Jour 1', dataIndex: 'day_court_1', },
                {header: 'Heure 1', dataIndex: 'hour_court_1', },
                {header: 'Gymnase 2', dataIndex: 'court_2', },
                {header: 'Jour 2', dataIndex: 'day_court_2', },
                {header: 'Heure 2', dataIndex: 'hour_court_2', },
            ],
        },
        {
            header: 'Adhésion payée ?',
            width: 200,
            dataIndex: 'is_paid',
            xtype: 'checkcolumn',
            listeners: {
                beforecheckchange: function () {
                    return false;
                }
            }
        },
        {
            header: 'Tournoi de brassage',
            defaults: {
                width: 200,
            },
            columns: [
                {
                    header: 'Participe ?',
                    dataIndex: 'is_seeding_tournament_requested',
                    xtype: 'checkcolumn',
                    listeners: {
                        beforecheckchange: function () {
                            return false;
                        }
                    },
                },
                {
                    header: 'Peut accueillir ?',
                    dataIndex: 'can_seeding_tournament_setup',
                    xtype: 'checkcolumn',
                    listeners: {
                        beforecheckchange: function () {
                            return false;
                        }
                    },
                },
            ],
        },

    ],
});