Ext.define('Ufolep13Volley.view.team.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.teamsgrid',
    title: 'Gestion des Equipes',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: {type: 'Teams'},
    plugins: 'gridfilters',
    columns: {
        items: [
            {
                header: 'Equipe',
                dataIndex: 'nom_equipe',
                width: 300,
                filter: {
                    type: 'string',
                },
            },
            {
                header: 'Club',
                dataIndex: 'club',
                width: 300,
                filter: {
                    type: 'string',
                },
            },
            {
                header: 'Compétition',
                dataIndex: 'libelle_competition',
                width: 300,
                filter: {
                    type: 'string',
                },
            },
            {
                header: 'Inscrite à la coupe ?',
                dataIndex: 'is_cup_registered',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
                        return false;
                    }
                },
                filter: {
                    type: 'boolean',
                },
            },
            {
                header: 'Engagée ?',
                dataIndex: 'is_active_team',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
                        return false;
                    }
                },
                filter: {
                    type: 'boolean',
                },
            },
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Créer une équipe',
                    action: 'add'
                },
                {
                    text: 'Editer équipe',
                    action: 'edit'
                },
                {
                    text: 'Supprimer',
                    action: 'delete'
                }
            ]
        },
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        }
    ]
});