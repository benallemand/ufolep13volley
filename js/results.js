Ext.onReady(function() {
    Ext.Loader.setPath('Ext.ux', 'js/libs');
    Ext.require('Ext.ux.ColumnAutoWidthPlugin');
    Ext.create('Ext.Button', {
        text: 'Derniers résultats...',
        margin: 10,
        renderTo: Ext.get('resultats'),
        handler: function() {
            Ext.create('Ext.window.Window', {
                title: 'Derniers résultats',
                maximizable: true,
                height: 400,
                width: 900,
                layout: 'fit',
                items: {
                    xtype: 'grid',
                    autoScroll: true,
                    plugins: [
                        Ext.create('Ext.ux.ColumnAutoWidthPlugin', {})
                    ],
                    columns: {
                        items: [
                            {
                                header: 'competition',
                                dataIndex: 'competition'
                            },
                            {
                                header: 'division',
                                dataIndex: 'division'
                            },
                            {
                                header: 'equipe_domicile',
                                dataIndex: 'equipe_domicile'
                            },
                            {
                                header: 'equipe_exterieur',
                                dataIndex: 'equipe_exterieur'
                            },
                            {
                                header: 'set1',
                                dataIndex: 'set1'
                            },
                            {
                                header: 'set2',
                                dataIndex: 'set2'
                            },
                            {
                                header: 'set3',
                                dataIndex: 'set3'
                            },
                            {
                                header: 'set4',
                                dataIndex: 'set4'
                            },
                            {
                                header: 'set5',
                                dataIndex: 'set5'
                            },
                            {
                                header: 'date_reception',
                                dataIndex: 'date_reception'
                            }
                        ],
                        defaults: {
                            autoWidth: true
                        }
                    },
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
            }).show();
        }
    });
});