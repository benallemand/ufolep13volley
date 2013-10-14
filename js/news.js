Ext.onReady(function() {
    Ext.tip.QuickTipManager.init();
    Ext.create('Ext.grid.Panel', {
        renderTo: Ext.get('news'),
        autoScroll: true,
        title: 'Quelques news...',
        columns: [
            {
                xtype: 'datecolumn',
                header: 'Date',
                dataIndex: 'date_news',
                format: 'd/m/Y',
                flex: 3
            },
            {
                header: 'Sujet',
                dataIndex: 'titre_news',
                flex: 10
            },
            {
                xtype: 'actioncolumn',
                items: [
                    {
                        icon: 'images/file.gif',
                        tooltip: 'Voir',
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.Msg.alert(rec.get('titre_news'), rec.get('texte_news'));
                        }
                    }
                ],
                flex: 1
            }
        ],
        store: Ext.create('Ext.data.Store', {
            fields: [
                'id_news',
                {
                    name: 'date_news',
                    type: 'date',
                    dateFormat: 'Y-m-d'
                },
                'titre_news',
                'texte_news'
            ],
            proxy: {
                type: 'rest',
                url: 'ajax/news.php',
                reader: {
                    type: 'json',
                    root: 'results'
                },
                writer: {
                    type: 'json'
                },
                listeners: {
                    exception: function(proxy, response, operation) {
                        var responseJson = Ext.decode(response.responseText);
                        Ext.MessageBox.show({
                            title: 'Erreur',
                            msg: responseJson.message,
                            icon: Ext.MessageBox.ERROR,
                            buttons: Ext.Msg.OK
                        });
                    }
                }
            },
            autoLoad: true,
            autoSync: true
        }),
        dockedItems: [
            {
                xtype: 'toolbar',
                hidden: true,
                dock: 'top',
                defaults: {
                    scale: 'medium'
                },
                items: [
                    {
                        icon: 'images/ajout.gif',
                        text: 'Ajouter',
                        tooltip: 'Ajouter',
                        handler: function(button) {
                            //button.up('grid').getStore().insert(0, record);
                        }
                    },
                    {
                        icon: 'images/modif.gif',
                        text: 'Modifier',
                        tooltip: 'Modifier',
                        handler: function(button) {
                            var rec = button.up('grid').getView().getSelectionModel().getSelection()[0];
                            Ext.Msg.show({
                                title: 'Modifier la news',
                                msg: 'Entrer le nouveau texte:',
                                prompt: true,
                                multiline: true,
                                width: 500,
                                height: 300,
                                buttons: Ext.Msg.OKCANCEL,
                                icon: Ext.Msg.QUESTION,
                                defaultTextHeight: 170,
                                value: rec.get('texte_news'),
                                fn: function(btn, text) {
                                    if (btn === 'ok') {
                                        rec.set('texte_news', text);
                                    }
                                }
                            });
                        }
                    },
                    {
                        icon: 'images/delete.gif',
                        tooltip: 'Supprimer',
                        text: 'Supprimer',
                        handler: function(button) {
                            var rec = button.up('grid').getView().getSelectionModel().getSelection()[0];
                            Ext.Msg.show({
                                title: 'Effacer la news ?',
                                msg: 'Vous allez effacer une news, confirmez vous cette action ?',
                                buttons: Ext.Msg.OKCANCEL,
                                icon: Ext.Msg.QUESTION,
                                fn: function(btn) {
                                    if (btn === 'ok') {
                                        button.up('grid').getStore().remove(rec);
                                    }
                                }
                            });
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
                var toolbars = Ext.ComponentQuery.query('grid[title=Quelques news...] > toolbar');
                toolbars[0].show();
            }
        }
    });
});