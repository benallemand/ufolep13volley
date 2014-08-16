Ext.define('Ufolep13Volley.view.site.ChampionshipPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.championshipPanel',
    layout: 'border',
    defaults: {
        border: false
    },
    items: [
        {
            region: 'north',
            xtype: 'headerPanel'
        },
        {
            region: 'north',
            xtype: 'titlePanel'
        },
        {
            region: 'north',
            xtype: 'limitDatePanel'
        },
        {
            region: 'center',
            xtype: 'tabpanel',
            items: [
                {
                    xtype: 'gridRanking'
                },
                {
                    xtype: 'gridMatches'
                }
            ]
        }
    ]
});
