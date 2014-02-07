Ext.onReady(function() {
    Ext.QuickTips.init();
    var storeClassement = Ext.create('Ext.data.Store', {
        fields: [
            {
                name: 'id_equipe',
                type: 'int'
            },
            {
                name: 'code_competition',
                type: 'string'
            },
            {
                name: 'rang',
                type: 'int'
            },
            {
                name: 'equipe',
                type: 'string'
            },
            {
                name: 'points',
                type: 'int'
            },
            {
                name: 'joues',
                type: 'int'
            },
            {
                name: 'gagnes',
                type: 'int'
            },
            {
                name: 'perdus',
                type: 'int'
            },
            {
                name: 'sets_pour',
                type: 'int'
            },
            {
                name: 'sets_contre',
                type: 'int'
            },
            {
                name: 'diff',
                type: 'int'
            },
            {
                name: 'coeff_s',
                type: 'float'
            },
            {
                name: 'points_pour',
                type: 'int'
            },
            {
                name: 'points_contre',
                type: 'int'
            },
            {
                name: 'coeff_p',
                type: 'float'
            },
            {
                name: 'penalites',
                type: 'int'
            }
        ],
        proxy: {
            type: 'ajax',
            url: 'ajax/getClassement.php',
            extraParams: {
                competition: competition,
                division: division
            },
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true
    });
    Ext.create('Ext.grid.Panel', {
        renderTo: Ext.get('classement'),
        title: 'Classement',
        store: storeClassement,
        width: 1000,
        columns: {
            items: [
                {
                    header: '',
                    dataIndex: 'rang',
                    flex: null,
                    width: 20,
                    align: 'center'
                },
                {
                    header: 'Equipe',
                    dataIndex: 'equipe',
                    flex: 2
                },
                {
                    header: 'Points',
                    dataIndex: 'points',
                    align: 'center'
                },
                {
                    header: 'Joués',
                    dataIndex: 'joues',
                    align: 'center'
                },
                {
                    header: 'Gagnés',
                    dataIndex: 'gagnes',
                    align: 'center'
                },
                {
                    header: 'Perdus',
                    dataIndex: 'perdus',
                    align: 'center'
                },
                {
                    header: 'Sets Pour',
                    dataIndex: 'sets_pour',
                    align: 'center'
                },
                {
                    header: 'Sets Contre',
                    dataIndex: 'sets_contre',
                    align: 'center'
                },
                {
                    header: 'Difference',
                    dataIndex: 'diff',
                    align: 'center'
                },
                {
                    header: 'Coeff Sets',
                    dataIndex: 'coeff_s',
                    align: 'center'
                },
                {
                    header: 'Pts Pour',
                    dataIndex: 'points_pour',
                    align: 'center'
                },
                {
                    header: 'Pts Contre',
                    dataIndex: 'points_contre',
                    align: 'center'
                },
                {
                    header: 'Coeff Points',
                    dataIndex: 'coeff_p',
                    align: 'center'
                },
                {
                    header: 'Pénalités',
                    dataIndex: 'penalites',
                    align: 'center'
                },
                {
                    header: 'Administration',
                    xtype: 'actioncolumn',
                    hideable: false,
                    hidden: true,
                    items: [
                        {
                            icon: 'images/moins.png',
                            tooltip: 'Ajouter un point de pénalité',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                Ext.Msg.show({
                                    title: 'Pénalité',
                                    msg: 'Voulez-vous ajouter un point de pénalité à cette équipe ?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    icon: Ext.Msg.QUESTION,
                                    fn: function(btn) {
                                        if (btn === 'ok') {
                                            Ext.Ajax.request({
                                                url: 'ajax/penalite.php',
                                                params: {
                                                    type: 'ajout',
                                                    compet: rec.get('code_competition'),
                                                    equipe: rec.get('id_equipe')
                                                },
                                                success: function(response) {
                                                    var responseJson = Ext.decode(response.responseText);
                                                    Ext.Msg.alert('Info', responseJson.message);
                                                    storeClassement.load();
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        },
                        {
                            icon: 'images/plus.png',
                            tooltip: 'Enlever un point de pénalité',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                Ext.Msg.show({
                                    title: 'Pénalité',
                                    msg: 'Voulez-vous enlever un point de pénalité à cette équipe ?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    icon: Ext.Msg.QUESTION,
                                    fn: function(btn) {
                                        if (btn === 'ok') {
                                            Ext.Ajax.request({
                                                url: 'ajax/penalite.php',
                                                params: {
                                                    type: 'suppression',
                                                    compet: rec.get('code_competition'),
                                                    equipe: rec.get('id_equipe')
                                                },
                                                success: function(response) {
                                                    var responseJson = Ext.decode(response.responseText);
                                                    Ext.Msg.alert('Info', responseJson.message);
                                                    storeClassement.load();
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        },
                        {
                            icon: 'images/delete.gif',
                            tooltip: 'Supprimer cette équipe de la compétition',
                            handler: function(grid, rowIndex) {
                                var rec = grid.getStore().getAt(rowIndex);
                                Ext.Msg.show({
                                    title: 'Suppression',
                                    msg: 'Cette opération entrainera la suppression de cette équipe de cette compétition ! Êtes-vous sur ?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    icon: Ext.Msg.QUESTION,
                                    fn: function(btn) {
                                        if (btn === 'ok') {
                                            Ext.Ajax.request({
                                                url: 'ajax/supprimerEquipeCompetition.php',
                                                params: {
                                                    compet: rec.get('code_competition'),
                                                    equipe: rec.get('id_equipe')
                                                },
                                                success: function(response) {
                                                    var responseJson = Ext.decode(response.responseText);
                                                    Ext.Msg.alert('Info', responseJson.message);
                                                    storeClassement.load();
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }
                    ]
                }
            ],
            defaults: {
                flex: 1
            }
        },
        dockedItems: [
            {
                xtype: 'toolbar',
                dock: 'top',
                layout: 'hbox',
                items: [
                    {
                        xtype: 'combo',
                        width: 400,
                        fieldLabel: 'Filtre sur équipe',
                        store: storeClassement,
                        displayField: 'equipe',
                        valueField: 'equipe',
                        listeners: {
                            change: function(combo, newVal, oldVal) {
                                var gridMatches = Ext.ComponentQuery.query('grid[title=Matches]')[0];
                                if (newVal === null) {
                                    gridMatches.getStore().clearFilter();
                                    return;
                                }
                                gridMatches.getStore().clearFilter(true);
                                gridMatches.getStore().filter([
                                    {
                                        filterFn: function(item) {
                                            return ((item.get("equipe_dom") === newVal) || (item.get("equipe_ext") === newVal));
                                        }
                                    }
                                ]);
                            }
                        }
                    },
                    {
                        xtype: 'button',
                        width: 120,
                        text: 'Voir tout',
                        handler: function() {
                            var comboFiltre = Ext.ComponentQuery.query('combo[fieldLabel=Filtre sur équipe]')[0];
                            comboFiltre.clearValue();
                        }
                    }
                ]
            }
        ]
    });
    Ext.Ajax.request({
        url: 'ajax/getSessionRights.php',
        success: function(response) {
            var responseJson = Ext.decode(response.responseText);
            if (responseJson.message === 'admin') {
                var adminColumns = Ext.ComponentQuery.query('actioncolumn[text=Administration]');
                Ext.each(adminColumns, function(adminColumn) {
                    adminColumn.show();
                });
            }
        }
    });
});