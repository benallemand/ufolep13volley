Ext.define('Ufolep13Volley.view.site.MatchesPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.matchesPanel',
    layout: 'border',
    autoScroll: true,
    defaults: {
        border: false
    },
    items: [
        {
            region: 'north', xtype: 'headerPanel'
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
            layout: 'fit',
            items: {
                xtype: 'gridMatches'
            }
        }
    ]
});
