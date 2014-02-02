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
        store: storeClassement,
        width: 1000,
        columns: {
            items: [
                {
                    header: '',
                    dataIndex: 'rang',
                    flex: null,
                    width: 20
                },
                {
                    header: 'Equipe',
                    dataIndex: 'equipe',
                    flex: 2
                },
                {
                    header: 'Pts',
                    dataIndex: 'points'
                },
                {
                    header: 'Jou.',
                    dataIndex: 'joues'
                },
                {
                    header: 'Gag.',
                    dataIndex: 'gagnes'
                },
                {
                    header: 'Per.',
                    dataIndex: 'perdus'
                },
                {
                    header: 'Sets P.',
                    dataIndex: 'sets_pour'
                },
                {
                    header: 'Sets C.',
                    dataIndex: 'sets_contre'
                },
                {
                    header: 'Diff.',
                    dataIndex: 'diff'
                },
                {
                    header: 'Coeff S.',
                    dataIndex: 'coeff_s'
                },
                {
                    header: 'Pts P.',
                    dataIndex: 'points_pour'
                },
                {
                    header: 'Pts C.',
                    dataIndex: 'points_contre'
                },
                {
                    header: 'Coeff P.',
                    dataIndex: 'coeff_p'
                },
                {
                    header: 'Pnlts',
                    dataIndex: 'penalites'
                },
                {
                    header: 'Admin.',
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
        }
    });
    Ext.Ajax.request({
        url: 'ajax/getSessionRights.php',
        success: function(response) {
            var responseJson = Ext.decode(response.responseText);
            if (responseJson.message === 'admin') {
                var adminColumn = Ext.ComponentQuery.query('actioncolumn[text=Admin.]')[0];
                adminColumn.show();
            }
        }
    });
});