Ext.define('Ufolep13Volley.view.rank.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridRanking',
    title: 'Classement',
    store: 'Classement',
    autoScroll: true,
    viewConfig: {
        getRowClass: function (record, rowIndex, rowParams, store) {
            if (record.get('joues') == 0) {
                return '';
            }
            var is_same_rank = false;
            var record_same_rank = null;
            store.each(function (current_record) {
                if (current_record.get('id_equipe') == record.get('id_equipe')) {
                    is_same_rank = false;
                    return;
                }
                if (current_record.get('points') != record.get('points')) {
                    is_same_rank = false;
                    return;
                }
                if (current_record.get('joues') != record.get('joues')) {
                    is_same_rank = false;
                    return;
                }
                if (current_record.get('diff') != record.get('diff')) {
                    is_same_rank = false;
                    return;
                }
                is_same_rank = true;
                record_same_rank = current_record;
                return false;
            });
            if (is_same_rank) {
                return 'grid-orange';
            }
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
                width: 20,
                align: 'center'
            },
            {
                header: 'Equipe',
                dataIndex: 'equipe',
                width: 180,
                renderer: function (val, meta, record) {
                    var url = 'annuaire.php?id=' + record.get('id_equipe') + '&c=' + record.get('code_competition');
                    return '<a href="' + url + '" target="blank">' + val + '</a>';
                },
                tdCls: 'x-style-cell'
            },
            {
                header: 'Points',
                width: 100,
                dataIndex: 'points',
                align: 'center'
            },
            {
                header: 'Joués',
                width: 100,
                dataIndex: 'joues',
                align: 'center'
            },
            {
                header: 'Gagnés',
                width: 100,
                dataIndex: 'gagnes',
                align: 'center'
            },
            {
                header: 'Perdus',
                width: 100,
                dataIndex: 'perdus',
                align: 'center',
                renderer: function (val, meta, record) {
                    if (record.get('matches_lost_by_forfeit_count') > 0) {
                        var tip = 'Dont ' + record.get('matches_lost_by_forfeit_count') + ' par forfait';
                        meta['tdAttr'] = 'data-qtip="' + tip + '"';
                        return val + '*';
                    }
                    return val;
                }
            },
            {
                header: 'Sets Pour',
                width: 100,
                dataIndex: 'sets_pour',
                align: 'center'
            },
            {
                header: 'Sets Contre',
                width: 100,
                dataIndex: 'sets_contre',
                align: 'center'
            },
            {
                header: 'Difference',
                width: 100,
                dataIndex: 'diff',
                align: 'center'
            },
            {
                header: 'Coeff Sets',
                width: 100,
                dataIndex: 'coeff_s',
                align: 'center'
            },
            {
                header: 'Pts Pour',
                width: 100,
                dataIndex: 'points_pour',
                align: 'center'
            },
            {
                header: 'Pts Contre',
                width: 100,
                dataIndex: 'points_contre',
                align: 'center'
            },
            {
                header: 'Coeff Points',
                width: 100,
                dataIndex: 'coeff_p',
                align: 'center'
            },
            {
                header: 'Pénalités',
                width: 100,
                dataIndex: 'penalites',
                align: 'center'
            }
        ]
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
                    queryMode: 'local',
                    displayField: 'equipe',
                    valueField: 'equipe',
                    listeners: {
                        change: function (combo, newVal) {
                            var gridMatches = Ext.ComponentQuery.query('grid[title=Matches]')[0];
                            if (newVal === null) {
                                gridMatches.getStore().clearFilter();
                                return;
                            }
                            gridMatches.getStore().clearFilter(true);
                            gridMatches.getStore().filter([
                                {
                                    filterFn: function (item) {
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
                    handler: function () {
                        var comboFiltre = Ext.ComponentQuery.query('combo[fieldLabel=Filtre sur équipe]')[0];
                        comboFiltre.clearValue();
                    }
                }
            ]
        }
    ]
});