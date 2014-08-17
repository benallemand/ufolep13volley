Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'team.GridMatches', 'team.FormDetails'],
    controllers: ['TeamManagement'],
    stores: ['MyMatches'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'border',
                width: 1280,
                height: 2048,
                items: [
                    {
                        region: 'north',
                        split: true,
                        xtype: 'headerPanel'
                    },
                    {
                        region: 'center',
                        flex: 1,
                        layout: 'border',
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
                                        icon: 'images/lock.png'
                                    },
                                    {
                                        text: 'Préférences',
                                        scale: 'large',
                                        icon: 'images/preferences.png',
                                        action: 'editPreferences'
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
                                flex: 1
                            }
                        ]
                    }
                ]
            }
        });
    }
});


