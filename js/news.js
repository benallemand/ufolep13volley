Ext.onReady(function() {
    Ext.define('News', {
        extend: 'Ext.data.Model',
        fields: [
            'id_news',
            {
                name: 'date_news',
                type: 'date',
                dateFormat: 'd/m/Y'
            },
            'titre_news',
            'texte_news'
        ]
    });
    afficheFormulaireNews = function(isUpdate) {
        Ext.create('Ext.window.Window', {
            title: 'News',
            height: 400,
            width: 700,
            modal: true,
            layout: 'fit',
            items: {
                xtype: 'form',
                layout: 'anchor',
                defaults: {
                    anchor: '100%',
                    margins: 10
                },
                items: [
                    {
                        xtype: 'datefield',
                        fieldLabel: 'Date',
                        name: 'date_news',
                        format: 'd/m/Y',
                        value: new Date()
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: 'Titre',
                        name: 'titre_news'
                    },
                    {
                        xtype: 'textarea',
                        fieldLabel: 'Texte',
                        name: 'texte_news'
                    }
                ],
                buttons: [
                    {
                        text: 'Annuler',
                        handler: function() {
                            this.up('window').close();
                        }
                    },
                    {
                        text: 'Sauver',
                        formBind: true,
                        disabled: true,
                        handler: function() {
                            var form = this.up('form').getForm();
                            if (form.isValid()) {
                                if (isUpdate) {
                                    form.updateRecord();
                                }
                                else {
                                    var newNews = Ext.create('News', form.getValues());
                                    Ext.ComponentQuery.query('grid[title=Quelques news...]')[0].getStore().insert(0, newNews);
                                }
                                this.up('window').close();
                            }
                        }
                    }
                ]
            }
        }).show();
        if (isUpdate) {
            var rec = Ext.ComponentQuery.query('grid[title=Quelques news...]')[0].getView().getSelectionModel().getSelection()[0];
            if (rec) {
                Ext.ComponentQuery.query('window[title=News] > form')[0].getForm().loadRecord(rec);
            }
            else {
                Ext.ComponentQuery.query('window[title=News]')[0].close();
            }
        }
    };
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
            model: 'News',
            sorters: [
                {
                    property: 'date_news',
                    direction: 'DESC'
                }
            ],
            filters : [
                {
                    filterFn : function(item) {
                        return item.get('date_news') > Ext.Date.subtract(new Date(), Ext.Date.MONTH, 6);
                    }
                }
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
                            afficheFormulaireNews(false);
                        }
                    },
                    {
                        icon: 'images/modif.gif',
                        text: 'Modifier',
                        tooltip: 'Modifier',
                        handler: function(button) {
                            afficheFormulaireNews(true);
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