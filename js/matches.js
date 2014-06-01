Ext.application({
    requires: ['Ext.panel.Panel'],
    views: [],
    controllers: ['Matches'],
    stores: ['Matches'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.grid.Panel', {
            renderTo: Ext.get('matches'),
            title: 'Matches',
            store: 'Matches',
            width: 1000,
            features: [
                {
                    ftype: 'grouping',
                    groupHeaderTpl: '{name}'
                }
            ],
            columns: {
                items: [
                    {
                        header: 'Code',
                        flex: 1,
                        dataIndex: 'code_match',
                        renderer: function(value, metaData, record) {
                            if (record.get('retard') === 1) {
                                metaData.tdAttr = 'style="background-color:VioletRed;color:black;" data-qtip="Match non renseigné de + de 10 jours!"';
                            }
                            if (record.get('retard') === 2) {
                                metaData.tdAttr = 'style="background-color:Red;color:black;" data-qtip="Match non renseigné de + de 15 jours!"';
                            }
                            return value;
                        }
                    },
                    {
                        header: 'Date',
                        flex: 3,
                        dataIndex: 'date_reception',
                        renderer: function(value, metaData, record) {
                            if (record.get('report') === true) {
                                metaData.tdAttr = 'style="background-color:Gold;color:black;" data-qtip="Match reporté"';
                            }
                            return Ext.Date.format(value, 'l d/m/Y') + ' ' + record.get('heure_reception');
                        }
                    },
                    {
                        header: 'Equipe Domicile',
                        flex: 2,
                        dataIndex: 'equipe_dom',
                        renderer: function(value, metaData, record) {
                            if (record.get('score_equipe_dom') === 3) {
                                metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                            }
                            return value;
                        }
                    },
                    {
                        header: 'Score',
                        dataIndex: 'score_equipe_dom',
                        flex: null,
                        width: 50,
                        renderer: function(val, meta, rec) {
                            if ((rec.get('score_equipe_dom') === 3) || (rec.get('score_equipe_ext') === 3)) {
                                return rec.get('score_equipe_dom') + '/' + rec.get('score_equipe_ext');
                            }
                        }
                    },
                    {
                        header: 'Equipe Extérieur',
                        flex: 2,
                        dataIndex: 'equipe_ext',
                        renderer: function(value, metaData, record) {
                            if (record.get('score_equipe_ext') === 3) {
                                metaData.tdAttr = 'style="background-color:GreenYellow;color:black;"';
                            }
                            return value;
                        }
                    },
                    {
                        header: 'Sets',
                        dataIndex: 'set_1_dom',
                        flex: 5,
                        renderer: function(val, meta, rec) {
                            var detailsMatch = '';
                            if ((rec.get('set_1_dom') !== 0) || (rec.get('set_1_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_1_dom') + '/' + rec.get('set_1_ext') + ' ';
                            }
                            if ((rec.get('set_2_dom') !== 0) || (rec.get('set_2_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_2_dom') + '/' + rec.get('set_2_ext') + ' ';
                            }
                            if ((rec.get('set_3_dom') !== 0) || (rec.get('set_3_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_3_dom') + '/' + rec.get('set_3_ext') + ' ';
                            }
                            if ((rec.get('set_4_dom') !== 0) || (rec.get('set_4_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_4_dom') + '/' + rec.get('set_4_ext') + ' ';
                            }
                            if ((rec.get('set_5_dom') !== 0) || (rec.get('set_5_ext') !== 0)) {
                                detailsMatch = detailsMatch + rec.get('set_5_dom') + '/' + rec.get('set_5_ext') + ' ';
                            }
                            return detailsMatch;
                        }
                    },
                    {
                        header: 'Administration',
                        xtype: 'actioncolumn',
                        hideable: false,
                        hidden: true,
                        items: [
                            {
                                icon: 'images/certified.png',
                                tooltip: 'Certifier avoir reçu la feuille de ce match',
                                getClass: function(value, meta, rec) {
                                    if (rec.get('certif') === true) {
                                        return "x-hide-display";
                                    }
                                },
                                handler: function(grid, rowIndex) {
                                    this.up('grid').fireEvent('itemcertifybuttonclick', grid, rowIndex);
                                }
                            },
                            {
                                icon: 'images/modif.gif',
                                tooltip: 'Modifier le score du match',
                                handler: function(grid, rowIndex) {
                                    this.up('grid').fireEvent('itemeditbuttonclick', grid, rowIndex);
                                }
                            },
                            {
                                icon: 'images/delete.gif',
                                tooltip: 'Supprimer ce match',
                                handler: function(grid, rowIndex) {
                                    this.up('grid').fireEvent('itemdeletebuttonclick', grid, rowIndex);
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
                    var adminColumns = Ext.ComponentQuery.query('actioncolumn[text=Administration]');
                    Ext.each(adminColumns, function(adminColumn) {
                        adminColumn.show();
                    });
                }
            }
        });
    }
});
