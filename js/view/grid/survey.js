Ext.define('Ufolep13Volley.view.grid.survey', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.survey_grid',
    title: 'Sondages',
    autoScroll: true,
    store: {
        type: 'survey',
        filters: [
            function (item) {
                return (item.get('on_time')
                    + item.get('spirit')
                    + item.get('referee')
                    + item.get('catering')
                    + item.get('global') > 0) || !Ext.isEmpty(item.get('comment'));
            }
        ],
        autoLoad: true,
    },
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Compte',
                dataIndex: 'login',
                width: 180,
            },
            {
                header: 'Equipe sondée',
                dataIndex: 'team_surveyed',
                width: 180,
            },
            {
                header: 'Match',
                dataIndex: 'match',
                width: 380,
            },
            {
                header: 'Ponctualité',
                dataIndex: 'on_time',
                width: 95,
            },
            {
                header: "Etat d'esprit",
                dataIndex: 'spirit',
                width: 105,
            },
            {
                header: "Arbitrage",
                dataIndex: 'referee',
                width: 95,
            },
            {
                header: "Apéro",
                dataIndex: 'catering',
                width: 95,
            },
            {
                header: "Global",
                dataIndex: 'global',
                width: 95,
            },
            {
                header: "Commentaires",
                dataIndex: 'comment',
                cellWrap: true,
                flex: 1
            },

        ]
    },
});