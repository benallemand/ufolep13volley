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
    },
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Compte',
                dataIndex: 'login',
                flex: 1
            },
            {
                header: 'Equipe sondée',
                dataIndex: 'team_surveyed',
                flex: 1
            },
            {
                header: 'Match',
                dataIndex: 'match',
                flex: 1
            },
            {
                header: 'Ponctualité',
                dataIndex: 'on_time',
                flex: 1
            },
            {
                header: "Etat d'esprit",
                dataIndex: 'spirit',
                flex: 1
            },
            {
                header: "Arbitrage",
                dataIndex: 'referee',
                flex: 1
            },
            {
                header: "Apéro",
                dataIndex: 'catering',
                flex: 1
            },
            {
                header: "Global",
                dataIndex: 'global',
                flex: 1
            },
            {
                header: "Commentaires",
                dataIndex: 'comment',
                flex: 1
            },

        ]
    },
});