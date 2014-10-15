Ext.define('Ufolep13Volley.view.site.HeaderPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.headerPanel',
    layout: 'fit',
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
    items: {
        xtype: 'titlePanel'
    }
});
