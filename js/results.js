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
                    columns: [
                        {
                            header: 'competition',
                            dataIndex: 'competition',
                            width: 180
                        },
                        {
                            header: 'division_journee',
                            dataIndex: 'division_journee',
                            width: 150
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
                            dataIndex: 'date_reception',
                            autoWidth: true
                        }
                    ],
                    store: Ext.create('Ext.data.Store', {
                        fields: [
                            'competition',
                            'division_journee',
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