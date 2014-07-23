Ext.define('Ufolep13Volley.view.site.HeaderPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.headerPanel',
    layout: {
        type: 'vbox',
        align: 'center'
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                '->',
                {
                    xtype: 'tbtext',
                    text: connectedUser,
                    style: {
                        color: 'red',
                        fontWeight: 'bold'
                    }
                },
                {
                    text: 'Se déconnecter',
                    scale: 'large',
                    icon: 'images/unlock.png',
                    href: "ajax/logout.php",
                    hrefTarget: '_self',
                    hidden: connectedUser === ''
                },
                {
                    text: 'Connexion',
                    scale: 'large',
                    icon: 'images/lock.png',
                    href: "portail.php",
                    hrefTarget: '_self',
                    hidden: connectedUser !== ''
                }
            ]
        },
        {
            xtype: 'mainMenu',
            dock: 'bottom'
        }
    ],
    items: [
        {
            xtype: 'banner'
        }
    ]
});
