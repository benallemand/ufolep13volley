Ext.define('Ufolep13Volley.view.new.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.NewGrid',
    flex: 1,
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
    store: 'News',
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
                        if (!rec) {
                            return;
                        }
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