Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['rank.Grid', 'match.Grid'],
    controllers: ['Matches', 'Classement'],
    stores: ['Matches', 'Classement'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.panel.Panel', {
            layout: 'border',
            renderTo: Ext.get('contenu'),
            width: 1000,
            height: 1200,
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
                            href: "includes/traitement.php?a=deconn",
                            hrefTarget: '_self',
                            hidden: connectedUser === ''
                        },
                        {
                            text: 'Connexion',
                            href: "portail.php",
                            hrefTarget: '_self',
                            hidden: connectedUser !== ''
                        }
                    ]
                }
            ],
            items: [
                {
                    region: 'north',
                    xtype: 'gridRanking',
                    flex: 1
                },
                {
                    region: 'center',
                    xtype: 'gridMatches',
                    flex: 2
                }
            ]
        });
        Ext.Ajax.request({
            url: 'ajax/getSessionRights.php',
            success: function(response) {
                var responseJson = Ext.decode(response.responseText);
                if (responseJson.message === 'admin') {
                    var adminColumns = Ext.ComponentQuery.query('actioncolumn[text=Administration]');
                    Ext.each(adminColumns, function(adminColumn) {
                        adminColumn.show();
                    });
                }
            }
        });
    }
});


