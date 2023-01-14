Ext.define('Ufolep13Volley.view.grid.Competitions', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.competitions_grid',
    title: 'Compétitions',
    autoScroll: true,
    store: {type: 'Competitions'},
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Code compétition',
                dataIndex: 'code_competition',
                flex: 1
            },
            {
                header: 'Libellé',
                dataIndex: 'libelle',
                flex: 1
            },
            {
                header: 'Code compétition maître',
                dataIndex: 'id_compet_maitre',
                flex: 1
            },
            {
                header: 'Date de début',
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'start_date',
                flex: 1
            },
            {
                header: "Date début d'inscription",
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'start_register_date',
                flex: 1
            },
            {
                header: "Date limite d'inscription",
                xtype: 'datecolumn',
                format: 'd/m/Y',
                dataIndex: 'limit_register_date',
                flex: 1
            },
            {
                header: 'Matchs aller-retour ?',
                dataIndex: 'is_home_and_away',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
                        return false;
                    }
                }
            }
        ]
    }
});