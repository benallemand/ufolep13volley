Ext.application({
    requires: ['Ext.panel.Panel'],
    views: [],
    controllers: ['Classement'],
    stores: ['Classement'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.grid.Panel', {
            renderTo: Ext.get('classement'),
            title: 'Classement',
            store: 'Classement',
            width: 1000,
            viewConfig: {
                getRowClass: function(record, rowIndex, rowParams, store) {
                    var total = store.getCount();
                    var rang = record.get('rang');
                    if (rang >= total - 1) {
                        return 'grid-red';
                    }
                    if (rang <= 2) {
                        return 'grid-green';
                    }
                    return '';
                }
            },
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
                        flex: 2,
                        renderer: function(val, meta, record) {
                            var url = 'annuaire.php?id=' + record.get('id_equipe') + '&c=' + record.get('code_competition');
                            return '<a href="' + url + '" target="blank">' + val + '</a>';
                        }
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
                        align: 'center',
                        renderer: function(val, meta, record) {
                            if (record.get('matches_won_with_5_players_count') > 0) {
                                var tip = 'Dont ' + record.get('matches_won_with_5_players_count') + ' gagné(s) à 5';
                                meta['tdAttr'] = 'data-qtip="' + tip + '"';
                                return val + '*';
                            }
                            return val;
                        }
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
                                    this.up('grid').fireEvent('itemaddpenaltybuttonclick', grid, rowIndex);
                                }
                            },
                            {
                                icon: 'images/plus.png',
                                tooltip: 'Enlever un point de pénalité',
                                handler: function(grid, rowIndex) {
                                    this.up('grid').fireEvent('itemremovepenaltybuttonclick', grid, rowIndex);
                                }
                            },
                            {
                                icon: 'images/delete.gif',
                                tooltip: 'Supprimer cette équipe de la compétition',
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
                            store: 'Classement',
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
    }
});