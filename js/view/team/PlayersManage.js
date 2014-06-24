Ext.define('Ufolep13Volley.view.team.PlayersManage', {
    extend: 'Ext.window.Window',
    alias: 'widget.playersmanage',
    title: 'Gestion des joueurs/joueuses',
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'grid',
        store: 'MyPlayers',
        viewConfig: {
            getRowClass: function(record, rowIndex, rowParams, store) {
                if (record.get('est_actif') === false) {
                    return 'grid-red';
                }
                return '';
            }
        },
        columns: {
            items: [
                {
                    header: 'Prénom',
                    dataIndex: 'prenom'
                },
                {
                    header: 'Nom',
                    dataIndex: 'nom'
                },
                {
                    header: 'Numéro de licence',
                    dataIndex: 'num_licence'
                },
                {
                    header: 'Capitaine ?',
                    dataIndex: 'est_capitaine',
                    xtype: 'checkcolumn',
                    listeners: {
                        beforecheckchange: function() {
                            return false;
                        }
                    }
                },
                {
                    header: 'Photo',
                    dataIndex: 'path_photo',
                    width: 150,
                    flex: null,
                    renderer: function(value, meta, record) {
                        return '<img width="100" src="' + record.get('path_photo') + '" />';
                    }
                },
                {
                    header: 'Gestion',
                    xtype: 'actioncolumn',
                    width: 100,
                    flex: null,
                    items: [
                        {
                            icon: 'images/delete.gif',
                            handler: function(grid, rowIndex) {
                                var storeMyPlayers = grid.getStore();
                                var rec = storeMyPlayers.getAt(rowIndex);
                                Ext.Msg.show({
                                    title: 'Retirer un joueur',
                                    msg: 'Voulez-vous retirer ' + rec.get('prenom') + ' ' + rec.get('nom') + ' de votre équipe ?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    icon: Ext.Msg.QUESTION,
                                    fn: function(btn) {
                                        if (btn === 'ok') {
                                            Ext.Ajax.request({
                                                url: 'ajax/removePlayerFromMyTeam.php',
                                                params: {
                                                    id: rec.get('id')
                                                },
                                                success: function(response) {
                                                    var responseJson = Ext.decode(response.responseText);
                                                    Ext.Msg.alert('Info', responseJson.message);
                                                    storeMyPlayers.load();
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
                items: [
                    {
                        xtype: 'button',
                        text: 'Ajouter un joueur'
                    },
                    {
                        xtype: 'button',
                        text: "Modifier le capitaine",
                        action: 'modifyTeamCaptain'
                    }
                ]
            }
        ]
    }
});