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
            split: true,
            xtype: 'headerPanel'
        },
        {
            region: 'center',
            xtype: 'tabpanel',
            id: 'phonebooksContainer',
            items: []
        }
    ]
});
