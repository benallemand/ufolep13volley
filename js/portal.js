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
                                        scale: 'small',
                                        icon: 'images/svg/sportsman.svg',
                                        action: 'showManagePlayers'
                                    },
                                    {
                                        text: 'Gestion des gymnases/créneaux',
                                        scale: 'small',
                                        icon: 'images/svg/volleyball_court.svg',
                                        action: 'showTimeSlotsManage'
                                    },
                                    {
                                        text: 'Fiche Equipe',
                                        href: 'teamSheetPdf.php',
                                        scale: 'small',
                                        icon: 'images/svg/pdf.svg'
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
                                        scale: 'small',
                                        icon: 'images/svg/modify.svg'
                                    },
                                    {
                                        text: 'Changer de mot de passe',
                                        scale: 'small',
                                        icon: 'images/svg/lock.svg'
                                    },
                                    {
                                        text: 'Préférences',
                                        scale: 'small',
                                        icon: 'images/svg/preferences.svg',
                                        action: 'editPreferences'
                                    },
                                    {
                                        text: 'Historique',
                                        scale: 'small',
                                        icon: 'images/svg/history.svg',
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


