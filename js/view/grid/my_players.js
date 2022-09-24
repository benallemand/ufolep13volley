Ext.define('Ufolep13Volley.view.grid.my_players', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.grid_my_players',
    title: 'Mon équipe',
    store: 'my_players',
    viewConfig: {
        getRowClass: function (record) {
            if (record.get('est_actif') === false) {
                return 'grid-red';
            }
            return '';
        }
    },
    columns: [
        {
            header: 'Photo', dataIndex: 'photo',
            tdCls: 'x-style-cell'
        },
        {header: 'Prénom', dataIndex: 'prenom'},
        {header: 'Nom', dataIndex: 'nom'},
        {header: 'Numéro de licence', dataIndex: 'num_licence'},
        {
            xtype: 'checkcolumn', header: 'Capitaine', dataIndex: 'is_captain', listeners: {
                beforecheckchange: function () {
                    return false;
                }
            }
        },
        {
            xtype: 'checkcolumn', header: 'Responsable', dataIndex: 'is_leader', listeners: {
                beforecheckchange: function () {
                    return false;
                }
            }
        },
        {
            xtype: 'checkcolumn', header: 'Suppléant', dataIndex: 'is_vice_leader', listeners: {
                beforecheckchange: function () {
                    return false;
                }
            }
        },
    ],
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'combo',
                    fieldLabel: 'Recherche',
                    width: 400,
                    name: 'add_to_team_player_id',
                    store: 'Players',
                    queryMode: 'local',
                    displayField: 'full_name',
                    valueField: 'id',
                    forceSelection: true,
                    emptyText: "Taper votre recherche ici"
                },
                {
                    xtype: 'button',
                    text: "Ajouter à mon équipe",
                    action: 'add_to_team',
                    disabled: true
                },
                '->',
                'Pas trouvé ?',
                {
                    xtype: 'button',
                    text: "Créer",
                    action: 'new_player'
                },
            ]
        },
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'button',
                    text: "Enlever de mon équipe",
                    action: 'remove_from_team',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: "Passer responsable",
                    action: 'set_leader',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: "Passer responsable suppléant",
                    action: 'set_vice_leader',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: "Passer capitaine",
                    action: 'set_captain',
                    hidden: true,
                },
            ]
        },
    ],
});