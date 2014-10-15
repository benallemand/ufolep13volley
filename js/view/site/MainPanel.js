Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
    layout: 'border',
    defaults: {
        border: false
    },
    items: Ext.is.Phone ?
            [
                {
                    region: 'north',
                    height: 110,
                    xtype: 'headerPanel'
                },
                {
                    region: 'center',
                    layout: 'border',
                    items: [
                        {
                            region: 'west',
                            flex: 2,
                            split: true,
                            xtype: 'LastResultsGrid'
                        },
                        {
                            region: 'center',
                            flex: 3,
                            xtype: 'tabpanel',
                            items: [
                                {
                                    xtype: 'LastPostsGrid'
                                },
                                {
                                    xtype: 'WebSitesGrid'
                                }
                            ]
                        }
                    ]

                }
            ] :
            [
                {
                    region: 'north',
                    height: 360,
                    layout: 'border',
                    items: [
                        {
                            region: 'north',
                            height: 100,
                            layout: 'center',
                            defaults: {
                                border: false
                            },
                            items: {
                                layout: {
                                    type: 'vbox',
                                    align: 'center'
                                },
                                xtype: 'panel',
                                items: [
                                    {
                                        width: 500,
                                        height: 50,
                                        xtype: 'banner'
                                    },
                                    {
                                        width: 400,
                                        height: 50,
                                        xtype: 'image',
                                        src: './images/JeuAvantEnjeu.jpg'
                                    }
                                ]
                            }
                        },
                        {
                            region: 'center',
                            xtype: 'coverflow',
                            store: 'Images'
                        }
                    ]
                },
                {
                    region: 'north',
                    height: 110,
                    xtype: 'headerPanel'
                },
                {
                    region: 'center',
                    layout: 'border',
                    items: [
                        {
                            region: 'west',
                            flex: 2,
                            split: true,
                            xtype: 'LastResultsGrid'
                        },
                        {
                            region: 'center',
                            flex: 3,
                            xtype: 'tabpanel',
                            items: [
                                {
                                    xtype: 'LastPostsGrid'
                                },
                                {
                                    xtype: 'WebSitesGrid'
                                }
                            ]
                        }
                    ]

                }
            ],
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'bottom',
            items: [
                '->',
                {
                    xtype: 'tbtext',
                    text: 'UFOLEP 13 VOLLEY (c) 2014-2015',
                    style: {
                        color: '#0099CC',
                        fontWeight: 'bold'
                    }
                },
                '->',
                {
                    xtype: 'button',
                    icon: 'images/email-icon.png',
                    text: 'Contact',
                    scale: 'large',
                    href: 'mailto:benallemand@gmail.com'
                }
            ]
        }
    ]
});
