Ext.define('Ufolep13Volley.view.grid.survey', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.survey_grid',
    title: 'Sondages',
    autoScroll: true,
    store: {type: 'survey'},
    selType: 'checkboxmodel',
    columns: {
        items: [
            {
                header: 'Compte',
                dataIndex: 'login',
                flex: 1
            },
            {
                header: 'Match',
                dataIndex: 'code_match',
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