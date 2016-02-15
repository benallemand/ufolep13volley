Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'team.GridMatches', 'team.FormDetails', 'team.GridAlerts'],
    controllers: controllers,
    stores: ['MyMatches', 'Alerts'],
    models: ['Match', 'Alert'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'border',
                items: [
                    {
                        region: 'north',
                        split: true,
                        xtype: 'headerPanel'
                    },
                    {
                        region: 'center',
                        layout: Ext.is.Phone ? 'accordion' : 'anchor',
                        margin: Ext.is.Phone ? 0 : '0 50 0 50',
                        autoScroll: true,
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
                                    }
                                ]
                            },
                            {
                                xtype: 'toolbar',
                                enableOverflow: true,
                                dock: 'top',
                                items: [
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
                                    },
                                    {
                                        text: 'Historique',
                                        scale: 'large',
                                        icon: 'images/ic_history_24px.svg',
                                        action: 'showHistory'
                                    }
                                ]
                            }
                        ],
                        items: [
                            {
                                xtype: 'gridTeamMatches'
                            },
                            {
                                xtype: 'gridAlerts'
                            },
                            {
                                xtype: 'formTeamDetails'
                            }
                        ]
                    }
                ]
            }
        });
    }
});


