Ext.define('Ufolep13Volley.view.player.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.playersgrid',
    title: 'Gestion des joueurs',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Players',
    columns: {
        items: [
            {
                header: 'Photo',
                dataIndex: 'path_photo',
                width: 120,
                renderer: function(val) {
                    return '<img src="' + val + '" width="80px" height="100px">';
                }
            },
            {
                header: 'Nom',
                dataIndex: 'nom'
            },
            {
                header: 'Prenom',
                dataIndex: 'prenom'
            },
            {
                header: 'Sexe',
                dataIndex: 'sexe'
            },
            {
                header: 'Num�ro de licence',
                dataIndex: 'num_licence'
            },
            {
                header: 'Club',
                dataIndex: 'club',
                flex: 1
            },
            {
                header: 'Equipes',
                dataIndex: 'teams_list',
                flex: 1
            },
            {
                header: 'Valide',
                dataIndex: 'est_licence_valide',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function() {
                        return false;
                    }
                }
            }
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    text: 'Associer � un club'
                },
                {
                    text: 'Associer � une �quipe'
                },
                {
                    text: 'Cr�er un joueur'
                },
                {
                    text: 'Editer joueur'
                }
            ]
        }
    ]
});