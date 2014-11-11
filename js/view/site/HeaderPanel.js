Ext.define('Ufolep13Volley.view.site.HeaderPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.headerPanel',
    layout: 'fit',
    height: Ext.is.Phone ? 70 : 95,
    border: false,
    dockedItems: [
        {
            xtype: 'mainMenu',
            dock: 'top'
        }
    ],
    items: {
        xtype: 'titlePanel'
    }
});
