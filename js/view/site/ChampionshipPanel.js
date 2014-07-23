Ext.define('Ufolep13Volley.view.site.ChampionshipPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.championshipPanel',
    layout: {
        type: 'vbox',
        align: 'center'
    },
    autoScroll: true,
    items: {
        layout: 'border',
        width: 1280,
        height: 2048,
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
                flex: 1,
                layout: 'border',
                items: [
                    {
                        region: 'north',
                        xtype: 'gridRanking',
                        flex: 1
                    },
                    {
                        region: 'center',
                        xtype: 'gridMatches',
                        flex: 3
                    }
                ]
            }
        ]}
});
