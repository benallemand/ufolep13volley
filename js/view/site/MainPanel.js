Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
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
                region: 'center',
                flex: 1,
                layout: 'border',
                items: [
                    {
                        region: 'north',
                        flex: 1,
                        layout: 'border',
                        items: [
                            {
                                region: 'west',
                                xtype: 'LastPostsGrid',
                                flex: 1
                            },
                            {
                                flex: 1,
                                region: 'center',
                                items: {
                                    xtype: 'image',
                                    id: 'randomImage',
                                    src: ''
                                }
                            }
                        ]
                    },
                    {
                        region: 'center',
                        flex: 3,
                        layout: 'border',
                        items: [
                            {
                                region: 'north',
                                flex: 2,
                                xtype: 'LastResultsGrid'
                            },
                            {
                                region: 'center',
                                flex: 1,
                                xtype: 'WebSitesGrid'
                            }
                        ]
                    }
                ]
            }
        ]}
});
