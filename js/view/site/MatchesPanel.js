Ext.define('Ufolep13Volley.view.site.MatchesPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.matchesPanel',
    layout: 'border',
    defaults: {
        border: false
    },
    items: [
        {
            region: 'north',
            split: true,
            xtype: 'headerPanel'
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
