Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'team.GridMatches', 'team.FormDetails', 'team.GridAlerts'],
    controllers: ['TeamManagement'],
    stores: ['MyMatches', 'Alerts'],
    models: ['Match', 'Alert'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
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
                                enableOverflow: true,
                                dock: 'top',
                                items: [
                                    {
                                        text: 'Gestion des joueurs/joueuses',
                                        scale: 'large',
                                        icon: 'images/man.png',
                                        action: 'showManagePlayers'
                                    },
                                    {
                                        text: 'Gestion des gymnases/créneaux',
                                        scale: 'large',
                                        icon: 'images/volleyballcourt.jpg',
                                        action: 'showTimeSlotsManage'
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
                                layout: 'border',
                                flex: 1,
                                items: [
                                    {
                                        region: 'west',
                                        width: 550,
                                        xtype: 'gridAlerts'
                                    },
                                    {
                                        region: 'center',
                                        flex: 1,
                                        xtype: 'formTeamDetails'
                                    }
                                ]
                            }
                        ]
                    }
                ]
            }
        });
    }
});


