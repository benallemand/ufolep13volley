Ext.define('Ufolep13Volley.view.match.LastResultsGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.LastResultsGrid',
    title: 'Derniers résultats',
    columns: [
        {
            header: 'Compétition',
            dataIndex: 'competition',
            width: 250
        },
        {
            header: 'Journée',
            dataIndex: 'division_journee',
            width: 200,
            renderer: function(val, meta, record) {
                var url = record.get('url');
                return '<a href="' + url + '" target="blank">' + val + '</a>';
            }
        },
        {
            header: 'Domicile',
            dataIndex: 'equipe_domicile',
            width: 180,
            renderer: function(val, meta, record) {
                var displayValue = val;
                switch (record.get('code_competition')) {
                    case 'm':
                    case 'f':
                        displayValue = displayValue + ' (' + record.get('rang_dom') + ')';
                        break;
                    default :
                        break;
                }
                if (record.get('score_equipe_dom') > record.get('score_equipe_ext')) {
                    return '<span style="color:green;font-weight:bold">' + displayValue + '</span>';
                }
                return displayValue;
            }
        },
        {
            header: '',
            dataIndex: 'score_equipe_dom',
            width: 20
        },
        {
            header: '',
            dataIndex: 'score_equipe_ext',
            width: 20
        },
        {
            header: 'Extérieur',
            dataIndex: 'equipe_exterieur',
            width: 180,
            renderer: function(val, meta, record) {
                var displayValue = val;
                switch (record.get('code_competition')) {
                    case 'm':
                    case 'f':
                        displayValue = displayValue + ' (' + record.get('rang_ext') + ')';
                        break;
                    default :
                        break;
                }
                if (record.get('score_equipe_ext') > record.get('score_equipe_dom')) {
                    return '<span style="color:green;font-weight:bold">' + displayValue + '</span>';
                }
                return displayValue;
            }
        },
        {
            header: 'S1',
            dataIndex: 'set1',
            width: 55
        },
        {
            header: 'S2',
            dataIndex: 'set2',
            width: 55
        },
        {
            header: 'S3',
            dataIndex: 'set3',
            width: 55
        },
        {
            header: 'S4',
            dataIndex: 'set4',
            width: 55
        },
        {
            header: 'S5',
            dataIndex: 'set5',
            width: 55
        },
        {
            header: 'Date',
            xtype: 'datecolumn',
            format: 'd/m/Y',
            dataIndex: 'date_reception',
            width: 100
        }
    ],
    store: 'LastResults'
});