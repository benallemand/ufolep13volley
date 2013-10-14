Ext.onReady(function() {
    Ext.Loader.setPath('Ext.ux', 'js/libs');
    Ext.require('Ext.ux.ColumnAutoWidthPlugin');
    var panelPhotos = Ext.create('Ext.panel.Panel', {
        region: 'east',
        flex: 1,
        layout: 'fit',
        tpl: '<br/><br/><img name="image" width="314" height="235" alt="" src="{url}" />'
    });
    function pad(n, width, z) {
        z = z || '0';
        n = n + '';
        return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
    }
    ;
    var task = {
        run: function() {
            panelPhotos.update({
                url: 'images/photos/imagevolley' + pad(Ext.Number.randomInt(1, 20), 3) + '.jpg'
            });
        },
        interval: 5000
    };
    Ext.TaskManager.start(task);

    Ext.create('Ext.panel.Panel', {
        renderTo: Ext.get('accueil'),
        layout: 'border',
        height: 700,
        items: [
            {
                region: 'north',
                flex: 2,
                layout: 'border',
                items: [
                    {
                        region: 'center',
                        flex: 1,
                        xtype: 'grid',
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
                                {
                                    name: 'date_news',
                                    type: 'date',
                                    dateFormat: 'Y-m-d'
                                },
                                'titre_news',
                                'texte_news'
                            ],
                            proxy: {
                                type: 'ajax',
                                url: 'ajax/getLastNews.php',
                                reader: {
                                    type: 'json',
                                    root: 'results'
                                }
                            },
                            autoLoad: true
                        })
                    },
                    panelPhotos
                ]
            },
            {
                region: 'center',
                flex: 3,
                xtype: 'grid',
                autoScroll: true,
                title: 'Derniers résultats',
                plugins: [
                    Ext.create('Ext.ux.ColumnAutoWidthPlugin', {})
                ],
                columns: [
                    {
                        header: 'competition',
                        dataIndex: 'competition',
                        width: 220
                    },
                    {
                        header: 'division',
                        dataIndex: 'division',
                        autoWidth: true
                    },
                    {
                        header: 'equipe_domicile',
                        dataIndex: 'equipe_domicile',
                        autoWidth: true
                    },
                    {
                        header: 'equipe_exterieur',
                        dataIndex: 'equipe_exterieur',
                        autoWidth: true
                    },
                    {
                        header: 'set1',
                        dataIndex: 'set1',
                        autoWidth: true
                    },
                    {
                        header: 'set2',
                        dataIndex: 'set2',
                        autoWidth: true
                    },
                    {
                        header: 'set3',
                        dataIndex: 'set3',
                        autoWidth: true
                    },
                    {
                        header: 'set4',
                        dataIndex: 'set4',
                        autoWidth: true
                    },
                    {
                        header: 'set5',
                        dataIndex: 'set5',
                        autoWidth: true
                    },
                    {
                        header: 'date_reception',
                        dataIndex: 'date_reception',
                        autoWidth: true
                    }
                ],
                store: Ext.create('Ext.data.Store', {
                    fields: [
                        'competition',
                        'division',
                        'equipe_domicile',
                        'equipe_exterieur',
                        'set1',
                        'set2',
                        'set3',
                        'set4',
                        'set5',
                        'date_reception'
                    ],
                    proxy: {
                        type: 'ajax',
                        url: 'ajax/getLastResults.php',
                        reader: {
                            type: 'json',
                            root: 'results'
                        }
                    },
                    autoLoad: true
                })
            }
        ]
    });
});