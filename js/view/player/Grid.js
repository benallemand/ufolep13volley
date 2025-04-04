Ext.define('Ufolep13Volley.view.player.Grid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.playersgrid',
    title: 'Gestion des joueurs',
    store: {type: 'Players'},
    viewConfig: {
        enableTextSelection: true,
        getRowClass: function (record) {
            if (record.get('est_actif') === false) {
                return 'grid-red';
            }
            return '';
        }
    },
    columns: {
        items: [
            {
                header: 'Nom',
                dataIndex: 'nom',
                tdCls: 'x-style-cell'
            },
            {
                header: 'Prenom',
                dataIndex: 'prenom',
                tdCls: 'x-style-cell'
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
                header: "Date d'homologation",
                dataIndex: 'date_homologation',
                xtype: 'datecolumn',
                format: 'd/m/Y'
            },
            {
                header: 'Club',
                dataIndex: 'club',
                flex: 1
            },
            {
                header: 'Equipes actives',
                dataIndex: 'active_teams_list',
                flex: 1
            },
            {
                header: 'Equipes inactives',
                hidden: true,
                dataIndex: 'inactive_teams_list',
                flex: 1
            },
            {
                header: 'Valide',
                dataIndex: 'est_actif',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
                        return false;
                    }
                },
                filter: {
                    type: 'boolean',
                },
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
                },
                {
                    text: 'Importer un fichier de licences',
                    action: 'display_import_licence_file'
                },
            ]
        },
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs sans club',
                    action: 'filterPlayersWithoutClub'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs sans licence',
                    action: 'filterPlayersWithoutLicence'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs non valides',
                    action: 'filterInactivePlayers'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs dans 2 équipes',
                    action: 'filterPlayersWith2TeamsSameCompetition'
                },
                {
                    xtype: 'checkbox',
                    boxLabel: 'Joueurs engagés dans au moins une équipe',
                    action: 'filterPlayersEngaged'
                }
            ]
        }
    ]
});