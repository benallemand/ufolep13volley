Ext.define('Ufolep13Volley.view.grid.survey', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.survey_grid',
    title: 'Sondages',
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
    columns: [
        {
            header: 'Compte',
            dataIndex: 'login',
            width: 180,
        },
        {
            header: 'Equipe',
            dataIndex: 'surveyor',
            width: 180,
        },
        {
            header: 'Equipe sondée',
            dataIndex: 'surveyed',
            width: 180,
        },
        {
            header: 'Club',
            dataIndex: 'surveyed_club',
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

    ],
});