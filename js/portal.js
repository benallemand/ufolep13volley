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
                                        scale: 'medium',
                                        glyph: 'xe90b@icomoon',
                                        action: 'showManagePlayers'
                                    },
                                    {
                                        text: 'Gestion des gymnases/créneaux',
                                        scale: 'medium',
                                        glyph: 'xe90d@icomoon',
                                        action: 'showTimeSlotsManage'
                                    },
                                    {
                                        text: 'Fiche Equipe',
                                        href: 'teamSheetPdf.php',
                                        glyph: 'xf1c1@FontAwesome',
                                        scale: 'medium'
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
                                        glyph: 'xf044@FontAwesome',
                                        scale: 'medium'
                                    },
                                    {
                                        text: 'Changer de mot de passe',
                                        glyph: 'xf084@FontAwesome',
                                        scale: 'medium'
                                    },
                                    {
                                        text: 'Préférences',
                                        glyph: 'xf013@FontAwesome',
                                        scale: 'medium',
                                        action: 'editPreferences'
                                    },
                                    {
                                        text: 'Historique',
                                        glyph: 'xf1da@FontAwesome',
                                        scale: 'medium',
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
                            },
                            {
                                title: 'Photo',
                                layout: 'fit',
                                items: {
                                    xtype: 'image',
                                    id: 'teamPicture',
                                    src: ''
                                }
                            }
                        ]
                    }
                ]
            }
        });
    }
});


