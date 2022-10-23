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
            sorters: [
                {
                    property: 'competition',
                    direction: 'ASC'
                },
                {
                    property: 'club',
                    direction: 'ASC'
                }
            ]
        },
    selType: 'rowmodel',
    autoScroll: true,
    columns: [
        {header: "Nom d'équipe", dataIndex: 'new_team_name', width: 200,},
        {header: 'Compétition', dataIndex: 'competition', width: 200,},
        {header: 'Division', dataIndex: 'division', width: 200,},
        {header: 'Rang de départ', dataIndex: 'rank_start', width: 200,},
        {header: 'Remarques', dataIndex: 'remarks', width: 200,},
        {header: 'Club', dataIndex: 'club', width: 200,},
        {header: 'Ancien nom', dataIndex: 'old_team', width: 200,},
        {header: 'Responsable', dataIndex: 'leader', width: 200,},
        {header: 'Gymnase 1', dataIndex: 'court_1', hidden: false, width: 200,},
        {header: 'Jour 1', dataIndex: 'day_court_1', hidden: false, width: 200,},
        {header: 'Heure 1', dataIndex: 'hour_court_1', hidden: false, width: 200,},
        {header: 'Gymnase 2', dataIndex: 'court_2', hidden: false, width: 200,},
        {header: 'Jour 2', dataIndex: 'day_court_2', hidden: false, width: 200,},
        {header: 'Heure 2', dataIndex: 'hour_court_2', hidden: false, width: 200,},
        {header: "Date d'inscription", dataIndex: 'creation_date', xtype: 'datecolumn', format: 'd/m/Y H:i:s', hidden: false, width: 200,},
    ],
});