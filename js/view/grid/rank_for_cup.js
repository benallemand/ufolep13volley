Ext.define('Ufolep13Volley.view.grid.rank_for_cup', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.rank_for_cup_grid',
    title: 'Classement général',
    store: {type: 'rank_for_cup'},
    viewConfig: {
        enableTextSelection: true,
        getRowClass: function (record) {
            if (record.get('rang') <= 16) {
                return 'grid-green';
            }
            return '';
        }
    },
    columns: {
        items: [
            {
                header: 'Classement',
                dataIndex: 'rang',
            },
            {
                header: 'Equipe',
                dataIndex: 'equipe',
                width: 200,
                tdCls: 'x-style-cell',
            },
            {
                header: 'Poule',
                flex: 1,
                columns: [
                    {
                        text: 'rang',
                        width: 80,
                        dataIndex: 'rang_poule',
                    },
                    {
                        header: 'nb matchs',
                        width: 120,
                        dataIndex: 'nb_matchs',
                    },
                    {
                        text: 'poule',
                        width: 80,
                        dataIndex: 'division',
                        renderer: function (value, meta, record) {
                            return Ext.String.format("<a href='/new_site/#/championship/{0}/{1}' target='_blank'>{2}</a>",
                                record.get('code_competition'),
                                value,
                                value,
                            );
                        },
                    },
                ]
            },
            {
                header: 'Pondération',
                flex: 1,
                columns: [
                    {
                        header: 'Pts',
                        width: 100,
                        xtype: 'numbercolumn',
                        format: '0.0',
                        dataIndex: 'points_ponderes',
                    },
                    {
                        header: 'Diff Sets',
                        width: 100,
                        xtype: 'numbercolumn',
                        format: '0.0',
                        dataIndex: 'diff_sets_ponderes',
                    },
                    {
                        header: 'Diff Points',
                        width: 100,
                        xtype: 'numbercolumn',
                        format: '0.0',
                        dataIndex: 'diff_points_ponderes',
                    },
                ]
            },
            {
                header: 'Détails',
                flex: 1,
                defaults: {
                    flex: 1,
                },
                columns: [
                    {
                        header: 'Pts',
                        dataIndex: 'points',
                    },
                    {
                        header: 'Diff sets',
                        dataIndex: 'diff_sets',
                    },
                    {
                        header: 'Diff pts',
                        dataIndex: 'diff_points',
                    },
                    {
                        header: 'Joués',
                        dataIndex: 'joues',
                    },
                    {
                        header: 'Gagnés',
                        dataIndex: 'gagnes',
                    },
                    {
                        header: 'Perdus',
                        dataIndex: 'perdus',
                    },
                    {
                        header: 'Sets pour',
                        dataIndex: 'sets_pour',
                    },
                    {
                        header: 'Sets contre',
                        dataIndex: 'sets_contre',
                    },
                    {
                        header: 'Pts pour',
                        dataIndex: 'points_pour',
                    },
                    {
                        header: 'Pts contre',
                        dataIndex: 'points_contre',
                    },
                ]
            }
        ]
    },
});