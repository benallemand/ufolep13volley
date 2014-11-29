Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
    layout: Ext.is.Phone ? 'accordion' : 'anchor',
    autoScroll: true,
    border: false,
    margin: Ext.is.Phone ? 0 : '0 50 0 50',
    items: [
        {
            xtype: 'LastResultsGrid',
            maxHeight: 480
        },
        {
            xtype: 'LastPostsGrid',
            maxHeight: 480
        },
        {
            xtype: 'WebSitesGrid',
            maxHeight: 480
        }
    ],
    dockedItems: [
        Ext.is.Phone ? null : {
            height: 150,
            dock: 'top',
            border: false,
            layout: 'border',
            items: [
                {
                    region: 'west',
                    layout: 'center',
                    border: false,
                    items: {
                        layout: {
                            type: 'vbox',
                            align: 'center'
                        },
                        border: false,
                        xtype: 'panel',
                        items: [
                            {
                                height: 30,
                                xtype: 'banner'
                            },
                            {
                                height: 30,
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
            dock: 'top',
            xtype: 'headerPanel'
        },
        {
            xtype: 'toolbar',
            dock: 'bottom',
            border: false,
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
                '|',
                {
                    xtype: 'tbtext',
                    id: 'textShowLastCommit',
                    text: ''
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
        