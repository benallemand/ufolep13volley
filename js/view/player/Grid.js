Ext.define('Ufolep13Volley.view.player.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.playersgrid',
    title: 'Gestion des joueurs',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Players',
    viewConfig: {
        getRowClass: function (record, rowIndex, rowParams, store) {
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
                dataIndex: 'est_actif',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function () {
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
                    text: 'V�rifier une licence...',
                    action: 'showCheckLicence'
                },
                {
                    text: 'Associer � un club',
                    action: 'showClubSelect'
                },
                {
                    text: 'Associer � une �quipe',
                    action: 'showTeamSelect'
                },
                {
                    text: 'Cr�er un joueur',
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