Ext.onReady(function() {
    Ext.create('Ext.toolbar.Toolbar', {
        renderTo: Ext.get('menu'),
        defaultButtonUI: 'default',
        defaults: {
            flex: 1
        },
        items: [
            {
                text: 'Masculins',
                menu: [
                    {text: 'Division 1', handler: function() {
                            window.open('champ_masc.php?d=1', '_self', false);
                        }},
                    {text: 'Division 2', handler: function() {
                            window.open('champ_masc.php?d=2', '_self', false);
                        }},
                    {text: 'Division 3', handler: function() {
                            window.open('champ_masc.php?d=3', '_self', false);
                        }},
                    {text: 'Division 4', handler: function() {
                            window.open('champ_masc.php?d=4', '_self', false);
                        }},
                    {text: 'Division 5', handler: function() {
                            window.open('champ_masc.php?d=5', '_self', false);
                        }},
                    {text: 'Division 6', handler: function() {
                            window.open('champ_masc.php?d=6', '_self', false);
                        }},
                    {text: 'Division 7', handler: function() {
                            window.open('champ_masc.php?d=7', '_self', false);
                        }}
                ]
            },
            {
                text: 'Féminines',
                menu: [
                    {text: 'Division 1', handler: function() {
                            window.open('champ_fem.php?d=1', '_self', false);
                        }},
                    {text: 'Division 2', handler: function() {
                            window.open('champ_fem.php?d=2', '_self', false);
                        }},
                    {text: 'Championnats Phase 2', handler: function() {
                            window.open('phase2_fem.php', '_self', false);
                        }},
                    {text: 'Tournois Féminins', handler: function() {
                            window.open('tournois_fem.php', '_self', false);
                        }}
                ]
            },
            {
                text: 'Coupe Isoardi',
                menu: [
                    {text: 'Poule 1', handler: function() {
                            window.open('coupe.php?d=1', '_self', false);
                        }},
                    {text: 'Poule 2', handler: function() {
                            window.open('coupe.php?d=2', '_self', false);
                        }},
                    {text: 'Poule 3', handler: function() {
                            window.open('coupe.php?d=3', '_self', false);
                        }},
                    {text: 'Poule 4', handler: function() {
                            window.open('coupe.php?d=4', '_self', false);
                        }},
                    {text: 'Poule 5', handler: function() {
                            window.open('coupe.php?d=5', '_self', false);
                        }},
                    {text: 'Poule 6', handler: function() {
                            window.open('coupe.php?d=6', '_self', false);
                        }},
                    {text: 'Poule 7', handler: function() {
                            window.open('coupe.php?d=7', '_self', false);
                        }},
                    {text: 'Poule 8', handler: function() {
                            window.open('coupe.php?d=8', '_self', false);
                        }},
                    {text: 'Poule 9', handler: function() {
                            window.open('coupe.php?d=9', '_self', false);
                        }},
                    {text: 'Poule 10', handler: function() {
                            window.open('coupe.php?d=10', '_self', false);
                        }},
                    {text: 'Poule 11', handler: function() {
                            window.open('coupe.php?d=11', '_self', false);
                        }},
                    {text: 'Phases Finales', handler: function() {
                            window.open('coupe_pf.php?c=cf', '_self', false);
                        }}
                ]
            },
            {
                text: 'Coupe K. Hanna',
                menu: [
                    {text: 'Poule 1', handler: function() {
                            window.open('coupe_kh.php?d=1', '_self', false);
                        }},
                    {text: 'Poule 2', handler: function() {
                            window.open('coupe_kh.php?d=2', '_self', false);
                        }},
                    {text: 'Poule 3', handler: function() {
                            window.open('coupe_kh.php?d=3', '_self', false);
                        }},
                    {text: 'Poule 4', handler: function() {
                            window.open('coupe_kh.php?d=4', '_self', false);
                        }},
                    {text: 'Poule 5', handler: function() {
                            window.open('coupe_kh.php?d=5', '_self', false);
                        }},
                    {text: 'Phase Finale', handler: function() {
                            window.open('coupe_pf.php?c=kf', '_self', false);
                        }}
                ]
            },
            {
                text: 'Portail Equipes',
                menu: [
                    {text: 'Connexion Portail', handler: function() {
                            window.open('portail.php', '_self', false);
                        }},
                    {text: 'Annuaire Equipes', handler: function() {
                            window.open('annuaire.php', '_self', false);
                        }},
                    {text: 'La Commission', handler: function() {
                            window.open('commission.php', '_self', false);
                        }},
                    {text: 'Ecrire au Webmaster', handler: function() {
                            window.open('mailto:laurent.gorlier@ufolep13volley.org');
                        }}
                ]
            },
            {
                text: 'Documents',
                menu: [
                    {text: 'Infos Utiles', handler: function() {
                            window.open('infos_utiles/index.html', '_blank');
                        }},
                    {
                        text: 'Agenda',
                        handler: function() {
                            Ext.create('Ext.window.Window', {
                                title: 'Agenda',
                                maximizable: true,
                                height: 650,
                                width: 900,
                                layout: 'fit',
                                items: [
                                    {
                                        xtype: 'panel',
                                        autoScroll: true,
                                        html: '<iframe src="https://www.google.com/calendar/embed?title=Calendrier%20des%20comp%C3%A9titions%20UFOLEP%2013%20Volley-Ball&amp;showPrint=0&amp;showTabs=0&amp;showCalendars=0&amp;showTz=0&amp;height=600&amp;wkst=2&amp;bgcolor=%23FFFFFF&amp;src=05otpt1qjnn3s2f0m5ejmkmkgk%40group.calendar.google.com&amp;color=%23875509&amp;src=2bm73rmo3317odnv2t1a6j1g6k%40group.calendar.google.com&amp;color=%235229A3&amp;ctz=Europe%2FParis" style=" border-width:0 " width="800" height="600" frameborder="0" scrolling="no"></iframe>'
                                    }
                                ]
                            }).show();
                        }
                    }
                ]
            },
            {
                text: 'Forum',
                handler: function() {
                    window.open('http://ufolep13volley.forumzen.com/', '_blank');
                }
            }
        ]
    });
});