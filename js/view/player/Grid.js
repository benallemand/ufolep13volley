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
                header: 'Numéro de licence',
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
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Vérifier une licence...',
                    action: 'showCheckLicence'
                },
                {
                    text: 'Associer à un club',
                    action: 'showClubSelect'
                },
                {
                    text: 'Associer à une équipe',
                    action: 'showTeamSelect'
                },
                {
                    text: 'Créer un joueur',
                    action: 'addPlayer'
                },
                {
                    text: 'Editer joueur',
                    action: 'editPlayer'
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
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs sans photo',
                    action: 'filterPlayersWithoutPhoto'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs sans club',
                    action: 'filterPlayersWithoutClub'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs non valides',
                    action: 'filterInactivePlayers'
                },
                '->',
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        }
    ]
});