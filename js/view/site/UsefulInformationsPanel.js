Ext.define('Ufolep13Volley.view.site.UsefulInformationsPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.usefulInformationsPanel',
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
            layout: 'fit',
            defaults: {
                border: false
            },
            items: {
                xtype: 'panel',
                autoScroll: true,
                html: '<iframe src="infos_utiles/index.html" width="1000px" height="1900px"></iframe>'
            }
        }
    ]
});
