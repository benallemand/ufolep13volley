Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['team.GridMatches', 'team.FormDetails'],
    controllers: ['TeamManagement'],
    stores: ['MyMatches'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.panel.Panel', {
            layout: 'border',
            renderTo: Ext.get('portail'),
            width: 980,
            height: 1200,
            dockedItems: [
                {
                    xtype: 'toolbar',
                    dock: 'top',
                    items: [
                        '->',
                        {
                            xtype: 'tbtext',
                            text: 'Non connecté',
                            style: {
                                color: 'red',
                                fontWeight: 'bold'
                            }
                        },
                        {
                            text: 'Préférences',
                            scale: 'large',
                            icon: 'images/preferences.png',
                            action: 'editPreferences'
                        },
                        {
                            text: 'Se déconnecter',
                            scale: 'large',
                            icon: 'images/exit.png',
                            href: "ajax/logout.php",
                            hrefTarget: '_self'
                        }
                    ]
                }
            ],
            items: [
                {
                    region: 'north',
                    xtype: 'gridTeamMatches',
                    flex: 1
                },
                {
                    region: 'center',
                    xtype: 'formTeamDetails',
                    flex: 2,
                    dockedItems: [
                        {
                            xtype: 'toolbar',
                            dock: 'top',
                            items: [
                                {
                                    text: 'Gestions des joueurs/joueuses',
                                    scale: 'large',
                                    icon: 'images/man.png'
                                },
                                {
                                    text: 'Fiche Equipe',
                                    href: 'teamSheetPdf.php',
                                    scale: 'large',
                                    icon: 'images/pdf.png'
                                },
                                {
                                    text: 'Modifier les informations',
                                    scale: 'large',
                                    icon: 'images/modify.png'
                                },
                                {
                                    text: 'Changer de mot de passe',
                                    scale: 'large',
                                    icon: 'images/password.png'
                                }
                            ]
                        }
                    ]
                }
            ]
        });
    }
});


