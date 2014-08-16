Ext.define('Ufolep13Volley.view.site.PhonebookPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.phonebookPanel',
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
            id: 'phonebooksContainer',
            items: []
        }
    ]
});
