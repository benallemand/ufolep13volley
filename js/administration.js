Ext.Loader.setConfig({
    enabled: true,
    paths: {
        'Ext.ux': 'js/ux' //Should be the path to the ux folder.
    }
});

Ext.application({
    requires: ['Ext.container.Viewport', 'Ext.ux.ExportableGrid'],
    controllers: [
        'Administration',
        'manage_friendships',
        'manage_blacklist_by_city',
        'remove_duplicate_files',
        'certify_matchs',
        'flip_matchs',
        'retry_error_emails',
        'send_mail_team_recap',
        'download_diploma',
        'manage_survey',
        'manage_register',
        'generate_competition',
        'manage_commission',
    ],
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
                        defaultButtonUI: 'default',
                        items: [
                            {
                                xtype: 'button',
                                text: "RETOUR A L'ACCUEIL",
                                href: '/',
                                target: 'blank',
                                scale: 'medium',
                                glyph: 'xf015@FontAwesome',
                            },
                            {
                                xtype: 'button',
                                text: 'Menu',
                                scale: 'medium',
                                menu: [
                                    {
                                        text: 'Activité',
                                        glyph: 'xf1da@FontAwesome',
                                        action: 'displayActivity'
                                    },
                                    {
                                        text: 'Gestion des joueurs',
                                        glyph: 'xe90a@icomoon',
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
                                        glyph: 'xe900@icomoon',
                                        action: 'manageClubs'
                                    },
                                    {
                                        text: 'Gestion des équipes',
                                        glyph: 'xe905@icomoon',
                                        action: 'manageTeams'
                                    },
                                    {
                                        text: 'Gestion des journées',
                                        glyph: 'xf073@FontAwesome',
                                        action: 'manageDays'
                                    },
                                    {
                                        text: 'Gestion des compétitions',
                                        action: 'displayCompetitions'
                                    },
                                    {
                                        text: 'Gestion des créneaux',
                                        action: 'displayTimeslots'
                                    },
                                    {
                                        text: 'Gestion des divisions/poules',
                                        glyph: 'xf201@FontAwesome',
                                        action: 'manageRanks'
                                    },
                                    {
                                        text: 'Gestion des matches',
                                        glyph: 'xe909@icomoon',
                                        action: 'manageMatches'
                                    },
                                    {
                                        text: 'Gestion des dates limites',
                                        glyph: 'xf273@FontAwesome',
                                        action: 'manageLimitDates'
                                    },
                                    {
                                        text: 'Gestion des gymnases',
                                        glyph: 'xe90d@icomoon',
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
                                    },
                                    {
                                        text: 'Palmarès',
                                        glyph: 'xe906@icomoon',
                                        action: 'displayHallOfFame'
                                    },
                                    {
                                        text: 'Gestion des dates interdites par gymnase',
                                        action: 'displayBlacklistGymnase'
                                    },
                                    {
                                        text: 'Gestion des dates interdites par équipe',
                                        action: 'displayBlacklistTeam'
                                    },
                                    {
                                        text: 'Gestion des équipes interdites de jouer ensemble',
                                        action: 'displayBlacklistTeams'
                                    },
                                    {
                                        text: 'Gestion des dates interdites',
                                        action: 'displayBlacklistDate'
                                    }
                                ]
                            },
                            '->',
                            {
                                text: "Rafraîchir",
                                scale: 'medium',
                                glyph: 'xf021@FontAwesome',
                                handler: function () {
                                    Ext.each(Ext.ComponentQuery.query('tabpanel > grid'), function (grid_panel) {
                                        grid_panel.getStore().load();
                                    });

                                }
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