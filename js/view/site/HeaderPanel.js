Ext.define('Ufolep13Volley.view.site.HeaderPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.headerPanel',
    layout: 'border',
    height: 100,
    dockedItems: [
        {
            xtype: 'mainMenu',
            dock: 'top'
        }
    ],
    defaults: {
        border: false
    },
    items: [
        {
            region: 'center',
            xtype: 'titlePanel'
        }
    ]
});
