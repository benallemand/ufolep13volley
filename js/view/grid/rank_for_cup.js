Ext.define('Ufolep13Volley.view.grid.rank_for_cup', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.rank_for_cup_grid',
    title: 'Classement général',
    autoScroll: true,
    store: {type: 'rank_for_cup'},
    viewConfig: {
        getRowClass: function (record) {
            if (record.get('rang') <= 16) {
                return 'grid-green';
            }
            return '';
        }
    },
    columns: {
        defaults: {
            width: 100,
        },
        items: [
            {
                header: 'Rang',
                dataIndex: 'rang',
            },
            {
                header: 'Equipe',
                dataIndex: 'equipe',
                width: 250,
                tdCls: 'x-style-cell',
            },
            {
                header: 'Pts',
                dataIndex: 'points',
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
                header: 'Diff sets',
                dataIndex: 'diff_sets',
            },
            {
                header: 'Pts pour',
                dataIndex: 'points_pour',
            },
            {
                header: 'Pts contre',
                dataIndex: 'points_contre',
            },
            {
                header: 'Diff pts',
                dataIndex: 'diff_points',
            },
        ]
    },
});