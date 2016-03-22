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
                layout: 'border',
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        dock: 'top',
                        items: [
                            'ADMINISTRATION',
                            '->',
                            {
                                xtype: 'button',
                                scale: 'large',
                                text: "RETOUR A L'ACCUEIL",
                                handler: function () {
                                    window.open('.', '_self', false);
                                }
                            }
                        ]
                    }
                ],
                items: [
                    {
                        region: 'west',
                        collapsible: true,
                        title: 'Navigation',
                        split: true,
                        width: 200,
                        layout: 'anchor',
                        autoScroll: true,
                        defaults: {
                            anchor: '100%',
                            xtype: 'button',
                            margin: 5
                        },
                        items: [
                            {
                                text: 'Activité',
                                action: 'displayActivity'
                            },
                            {
                                text: 'Gestion des joueurs',
                                action: 'managePlayers'
                            },
                            {
                                text: 'Gestion des profils',
                                action: 'manageProfiles'
                            },
                            {
                                text: 'Gestion des utilisateurs',
                                action: 'manageUsers'
                            },
                            {
                                text: 'Gestion des clubs',
                                action: 'manageClubs'
                            },
                            {
                                text: 'Gestion des équipes',
                                action: 'manageTeams'
                            },
                            {
                                text: 'Gestion des journées',
                                action: 'manageDays'
                            },
                            {
                                text: 'Gestion des divisions/poules',
                                action: 'manageRanks'
                            },
                            {
                                text: 'Gestion des matches',
                                action: 'manageMatches'
                            },
                            {
                                text: 'Gestion des dates limites',
                                action: 'manageLimitDates'
                            },
                            {
                                text: 'Gestion des gymnases',
                                action: 'manageGymnasiums'
                            },
                            {
                                text: 'Planning de la semaine',
                                action: 'displayWeekSchedule'
                            },
                            {
                                text: 'Indicateurs',
                                action: 'displayIndicators'
                            }
                        ]
                    },
                    {
                        region: 'center',
                        title: 'Panneau Principal',
                        layout: 'fit',
                        xtype: 'tabpanel',
                        defaults: {
                            closable: true
                        }
                    }
                ]
            }
        });
    }
});