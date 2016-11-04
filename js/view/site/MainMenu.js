Ext.define('Ufolep13Volley.view.site.MainMenu', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.mainMenu',
    defaultButtonUI: 'default',
    enableOverflow: true,
    border: false,
    items: [
        {
            text: 'Accueil',
            scale: 'medium',
            glyph: 'xf015@FontAwesome',
            href: 'index.php',
            hrefTarget: '_self'
        },
        {
            text: 'Championnats',
            scale: 'medium',
            glyph: 'xe907@icomoon',
            menu: [
                {
                    text: 'Masculin (Mixte)',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'championship.php?d=1&c=m',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'championship.php?d=2&c=m',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 3',
                            href: 'championship.php?d=3&c=m',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 4',
                            href: 'championship.php?d=4&c=m',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 5a',
                            href: 'championship.php?d=5a&c=m',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 5b',
                            href: 'championship.php?d=5b&c=m',
                            hrefTarget: '_self'
                        },
                        // {
                        //     text: 'Division 6',
                        //     href: 'championship.php?d=6&c=m',
                        //     hrefTarget: '_self'
                        // },
                        {
                            text: 'Division 7',
                            href: 'championship.php?d=7&c=m',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: 'Féminin',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'championship.php?d=1&c=f',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'championship.php?d=2&c=f',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 3',
                            href: 'championship.php?d=3&c=f',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: '4x4 Mixte',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'championship.php?d=1&c=mo',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'championship.php?d=2&c=mo',
                            hrefTarget: '_self'
                        }
                    ]
                }
            ]
        },
        {
            text: 'Coupes',
            scale: 'medium',
            glyph: 'xe906@icomoon',
            menu: [
                {
                    text: 'Isoardi',
                    menu: [
                        {
                            text: 'Poule 1',
                            href: 'championship.php?d=1&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 2',
                            href: 'championship.php?d=2&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 3',
                            href: 'championship.php?d=3&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 4',
                            href: 'championship.php?d=4&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 5',
                            href: 'championship.php?d=5&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 6',
                            href: 'championship.php?d=6&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 7',
                            href: 'championship.php?d=7&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 8',
                            href: 'championship.php?d=8&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 9',
                            href: 'championship.php?d=9&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 10',
                            href: 'championship.php?d=10&c=c',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Phase Finale',
                            hidden: false,
                            href: 'cup.php?c=cf',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: 'Khoury Hanna',
                    menu: [
                        {
                            text: 'Poule 1',
                            href: 'championship.php?d=1&c=kh',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 2',
                            href: 'championship.php?d=2&c=kh',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 3',
                            href: 'championship.php?d=3&c=kh',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 4',
                            href: 'championship.php?d=4&c=kh',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 5',
                            href: 'championship.php?d=5&c=kh',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Phase Finale',
                            hidden: false,
                            href: 'cup.php?c=kf',
                            hrefTarget: '_self'
                        }
                    ]
                }
            ]
        },
        {
            text: 'Informations',
            scale: 'medium',
            glyph: 'xf05a@FontAwesome',
            menu: [
                {
                    text: 'Palmarès',
                    glyph: 'xe906@icomoon',
                    action: 'showHallOfFame'
                },
                {
                    text: 'Annuaire',
                    href: 'annuaire.php',
                    hrefTarget: '_self'
                },
                {
                    text: 'Gymnases',
                    action: 'showGymnasiumsMap'
                },
                {
                    text: 'Agenda',
                    handler: function () {
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
                },
                {
                    text: 'Infos Utiles',
                    href: 'index_infos_utiles.php',
                    hrefTarget: '_self'
                },
                {
                    text: 'Règlements',
                    menu: [
                        {
                            text: 'FIVB',
                            href: 'http://www.fivb.org/EN/Refereeing-Rules/documents/FINAL_2015_FR_V5_modifs_accepted%20(2).pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Général',
                            href: 'infos_utiles/Media/ReglementGeneral.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Championnat Féminin',
                            href: 'infos_utiles/Media/ReglementFeminin.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Championnat Masculin',
                            href: 'infos_utiles/Media/ReglementMasculin.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Championnat Mixte',
                            href: 'infos_utiles/Media/ReglementChampionnatMixte.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Coupe Koury Hanna',
                            href: 'infos_utiles/Media/ReglementKouryHanna.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Coupe Isoardi',
                            href: 'infos_utiles/Media/ReglementIsoardi.pdf',
                            hrefTarget: '_blank'
                        },
                        {
                            text: 'Bonus: Feuille de match',
                            href: 'infos_utiles/Media/FeuilleMatch.pdf',
                            hrefTarget: '_blank'
                        }
                    ]
                },
                {
                    text: 'Déclaration de sinistre',
                    handler: function () {
                        Ext.Msg.show({
                            title: 'Déclaration de sinistre',
                            msg: "Le document téléchargé doit être transmis à :<br/>\
Fédération des A. I. L, Service Apac, 192 rue Horace Bertin, 13005 Marseille<br/>\
La responsable Apac est : Céline Pouillot<br/>\
04 91 24 31 47 ou 61<br/>\
Pour votre information, le service APAC est ouvert :<br/>\
Du lundi au vendredi de 10h à 12h et de 14h30 à 17h<br/>\
- Sur place avec ou sans rendez-vous : 192 Rue Horace Bertin 13005 Marseille<br/>\
- Par tél. 04.91.24.31.47<br/>\
- Par mail<br/>\
Déléguée APAC Céline POUILLOT celine.pouillot@laligue13.fr<br/>\
Secrétariat APAC Aurore RACLOT apac@laligue13.fr <br/>\
Il faudra retourner par voie postale :<br/>\
- La déclaration de sinistre dûment remplie et signée<br/>\
- Le certificat médical original de constatation de blessure<br/>\
- La copie de la licence en cours du joueur blessé<br/>\
- La copie de la feuille de match",
                            buttons: Ext.Msg.OK,
                            icon: Ext.Msg.INFO,
                            buttonText: {
                                ok: 'Télécharger'
                            },
                            fn: function () {
                                window.open('infos_utiles/Media/DeclarationSinistreApac.pdf', '_blank');
                            }
                        });
                    }
                },
                {
                    text: 'Commission',
                    href: 'commission.php',
                    hrefTarget: '_self'
                },
                {
                    text: 'Contact',
                    handler: function () {
                        window.open('mailto:benallemand@gmail.com');
                    }
                },
                {
                    text: "Site de l'UFOLEP 13",
                    href: 'http://ufolep13.org/',
                    hrefTarget: '_blank'
                }
            ]
        },
        {
            text: 'Forum',
            scale: 'medium',
            glyph: 'xf1d7@FontAwesome',
            href: "http://ufolep13volley.forumzen.com",
            hrefTarget: '_blank'
        },
        {
            text: 'Version Mobile',
            scale: 'medium',
            glyph: 'xf10b@FontAwesome',
            href: '/new_site/',
            hrefTarget: '_self'
        },
        '->',
        {
            text: 'Mon Compte',
            scale: 'medium',
            glyph: 'xf007@FontAwesome',
            hidden: connectedUser === '',
            menu: [
                {
                    xtype: 'tbtext',
                    text: connectedUser,
                    style: {
                        color: 'red',
                        fontWeight: 'bold'
                    }
                },
                {
                    text: "Ma page (Responsable d'équipe uniquement)",
                    href: 'portail.php',
                    hrefTarget: '_self'
                },
                {
                    text: 'Se déconnecter',
                    scale: 'medium',
                    glyph: 'xf08b@FontAwesome',
                    href: "ajax/logout.php",
                    hrefTarget: '_self'
                }
            ]
        },
        {
            text: 'Connexion',
            scale: 'medium',
            glyph: 'xf090@FontAwesome',
            href: "portail.php",
            hrefTarget: '_self',
            hidden: connectedUser !== ''
        }
    ]
});
