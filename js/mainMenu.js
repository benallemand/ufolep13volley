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
    var getAdminButton = function(tableName, fields) {
        return {
            text: tableName,
            handler: function() {
                var columns = [];
                Ext.each(fields, function(field) {
                    columns.push({
                        header: field,
                        dataIndex: field
                    });
                });
                Ext.create('Ext.window.Window', {
                    title: tableName,
                    maximizable: true,
                    height: 400,
                    width: 900,
                    layout: 'fit',
                    items: {
                        xtype: 'grid',
                        autoScroll: true,
                        plugins: [
                            Ext.create('Ext.ux.ColumnAutoWidthPlugin', {})
                        ],
                        store: Ext.create('Ext.data.Store', {
                            fields: fields,
                            proxy: {
                                type: 'rest',
                                url: 'ajax/' + tableName + '.php',
                                reader: {
                                    type: 'json',
                                    root: 'results'
                                }
                            },
                            autoLoad: true
                        }),
                        columns: columns
                    }
                }).show();
            }
        };
    };
    Ext.Ajax.request({
        url: 'ajax/getSessionRights.php',
        success: function(response) {
            var responseJson = Ext.decode(response.responseText);
            if (responseJson.message === 'admin') {
                var menuPortailEquipes = Ext.ComponentQuery.query('button[text=Portail Equipes] > menu')[0];
                menuPortailEquipes.add({
                    text: 'Admin',
                    menu: [
                        getAdminButton('classements', ['code_competition', 'division', 'id_equipe', 'points', 'joues', 'gagnes', 'perdus', 'sets_pour', 'sets_contre', 'difference', 'coeff_sets', 'points_pour', 'points_contre', 'coeff_points', 'penalite']),
                        getAdminButton('competitions', ['id', 'code_competition', 'libelle', 'id_compet_maitre']),
                        getAdminButton('comptes_acces', ['id_equipe', 'login', 'password']),
                        getAdminButton('dates_limite', ['id_date', 'code_competition', 'division', 'date_limite']),
                        getAdminButton('details_equipes', ['id_equipe', 'responsable', 'telephone_1', 'telephone_2', 'email', 'gymnase', 'localisation', 'jour_reception', 'heure_reception', 'site_web', 'photo', 'fdm']),
                        getAdminButton('equipes', ['id_equipe', 'code_competition', 'nom_equipe']),
                        getAdminButton('images', ['id_image', 'categorie_image', 'chemin_image']),
                        getAdminButton('journees', ['id', 'code_competition', 'division', 'numero', 'nommage', 'libelle']),
                        getAdminButton('matches', ['id_match', 'code_match', 'code_competition', 'division', 'journee', 'id_equipe_dom', 'id_equipe_ext', 'score_equipe_dom', 'score_equipe_ext', 'set_1_dom', 'set_1_ext', 'set_2_dom', 'set_2_ext', 'set_3_dom', 'set_3_ext', 'set_4_dom', 'set_4_ext', 'set_5_dom', 'set_5_ext', 'heure_reception', 'date_reception', 'gagnea5_dom', 'gagnea5_ext', 'forfait_dom', 'forfait_ext', 'certif', 'report', 'retard'])
                    ]
                });
            }
        }
    });
});