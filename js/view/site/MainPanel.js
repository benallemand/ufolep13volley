Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
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
            region: 'center',
            xtype: 'tabpanel',
            items: [
                {
                    xtype: 'LastResultsGrid'
                },
                {
                    xtype: 'LastPostsGrid'
                },
                {
                    xtype: 'WebSitesGrid'
                }
            ]
        }
    ]
});
