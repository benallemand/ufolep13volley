Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['Administration'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.define('Ext.form.PasswordField', {
            extend: 'Ext.form.field.Text',
            alias: 'widget.passwordfield',
            inputType: 'password'
        });
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                title: 'ADMINISTRATION : Panneau Principal',
                layout: 'fit',
                xtype: 'tabpanel',
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        dock: 'top',
                        items: [
                            {
                                text: "RETOUR A L'ACCUEIL",
                                scale: 'small',
                                glyph: 'xf015@FontAwesome',
                                handler: function () {
                                    window.open('.', '_self', false);
                                }
                            }
                        ]
                    },
                    {
                        xtype: 'toolbar',
                        dock: 'left',
                        defaults: {
                            textAlign: 'left'
                        },
                        items: [
                            {
                                text: 'Activité',
                                glyph: 'xf1da@FontAwesome',
                                action: 'displayActivity'
                            },
                            {
                                text: 'Gestion des joueurs',
                                glyph: '0108@sport_4_ever',
                                action: 'managePlayers'
                            },
                            {
                                text: 'Gestion des profils',
                                glyph: 'xf084@FontAwesome',
                                action: 'manageProfiles'
                            },
                            {
                                text: 'Gestion des utilisateurs',
                                glyph: 'xf0c0@FontAwesome',
                                action: 'manageUsers'
                            },
                            {
                                text: 'Gestion des clubs',
                                glyph: 'xf247@FontAwesome',
                                action: 'manageClubs'
                            },
                            {
                                text: 'Gestion des équipes',
                                glyph: 'xf248@FontAwesome',
                                action: 'manageTeams'
                            },
                            {
                                text: 'Gestion des journées',
                                glyph: 'xf073@FontAwesome',
                                action: 'manageDays'
                            },
                            {
                                text: 'Gestion des divisions/poules',
                                glyph: 'xf201@FontAwesome',
                                action: 'manageRanks'
                            },
                            {
                                text: 'Gestion des matches',
                                glyph: '0123@sports_tfb',
                                action: 'manageMatches'
                            },
                            {
                                text: 'Gestion des dates limites',
                                glyph: 'xf273@FontAwesome',
                                action: 'manageLimitDates'
                            },
                            {
                                text: 'Gestion des gymnases',
                                glyph: '0063@sport_4_ever',
                                action: 'manageGymnasiums'
                            },
                            {
                                text: 'Planning de la semaine',
                                glyph: 'xf073@FontAwesome',
                                action: 'displayWeekSchedule'
                            },
                            {
                                text: 'Indicateurs',
                                glyph: 'xf071@FontAwesome',
                                action: 'displayIndicators'
                            }
                        ]
                    }
                ],
                defaults: {
                    closable: true
                }
            }
        });
    }
});