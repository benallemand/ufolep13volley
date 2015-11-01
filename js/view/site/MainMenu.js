Ext.define('Ufolep13Volley.view.site.MainMenu', {
    extend: 'Ext.toolbar.Toolbar',
    alias: 'widget.mainMenu',
    defaultButtonUI: 'default',
    enableOverflow: true,
    border: false,
    items: [
        '->',
        {
            text: 'Accueil',
            scale: 'large',
            icon: 'images/home.png',
            href: 'index.php',
            hrefTarget: '_self'
        },
        {
            text: 'Championnats',
            scale: 'large',
            icon: 'images/volleyball.png',
            menu: [
                {
                    text: 'Masculin (Mixte)',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'champ_masc.php?d=1',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'champ_masc.php?d=2',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 3',
                            href: 'champ_masc.php?d=3',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 4',
                            href: 'champ_masc.php?d=4',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 5',
                            href: 'champ_masc.php?d=5',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 6',
                            href: 'champ_masc.php?d=6',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 7',
                            href: 'champ_masc.php?d=7',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: 'Féminin',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'champ_fem.php?d=1',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'champ_fem.php?d=2',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 3',
                            href: 'champ_fem.php?d=3',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: '4x4 Mixte (A VENIR)',
                    menu: [
                        {
                            text: 'Division 1',
                            href: 'champ_mixte.php?d=1',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Division 2',
                            href: 'champ_mixte.php?d=2',
                            hrefTarget: '_self'
                        }
                    ]
                }
            ]
        },
        {
            text: 'Coupes',
            hidden: true,
            scale: 'large',
            icon: 'images/cup.png',
            menu: [
                {
                    text: 'Isoardi',
                    menu: [
                        {
                            text: 'Poule 1',
                            href: 'coupe.php?d=1',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 2',
                            href: 'coupe.php?d=2',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 3',
                            href: 'coupe.php?d=3',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 4',
                            href: 'coupe.php?d=4',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 5',
                            href: 'coupe.php?d=5',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 6',
                            href: 'coupe.php?d=6',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 7',
                            href: 'coupe.php?d=7',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 8',
                            href: 'coupe.php?d=8',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 9',
                            href: 'coupe.php?d=9',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 10',
                            href: 'coupe.php?d=10',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 11',
                            href: 'coupe.php?d=11',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Phase Finale',
                            //hidden: true,
                            href: 'coupe_pf.php',
                            hrefTarget: '_self'
                        }
                    ]
                },
                {
                    text: 'Khoury Hanna',
                    menu: [
                        {
                            text: 'Poule 1',
                            href: 'coupe_kh.php?d=1',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 2',
                            href: 'coupe_kh.php?d=2',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 3',
                            href: 'coupe_kh.php?d=3',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Poule 4',
                            href: 'coupe_kh.php?d=4',
                            hrefTarget: '_self'
                        },
                        {
                            text: 'Phase Finale',
                            //hidden: true,
                            href: 'coupe_kf.php',
                            hrefTarget: '_self'
                        }
                    ]
                }
            ]
        },
        {
            text: 'Informations',
            scale: 'large',
            icon: 'images/info30x30.png',
            menu: [
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
                            fn: function (btn) {
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
                }
            ]
        },
        {
            text: 'Forum',
            scale: 'large',
            icon: 'images/forum.png',
            href: "http://ufolep13volley.forumzen.com",
            hrefTarget: '_blank'
        },
        {
            text: 'Version Mobile',
            scale: 'large',
            icon: 'images/mobile.png',
            href: 'index_mobile.php',
            hrefTarget: '_self'
        },
        {
            text: 'Mon Compte',
            scale: 'large',
            icon: 'images/account.png',
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
                    scale: 'large',
                    icon: 'images/unlock.png',
                    href: "ajax/logout.php",
                    hrefTarget: '_self'
                }
            ]
        },
        {
            text: 'Connexion',
            scale: 'large',
            icon: 'images/lock.png',
            href: "portail.php",
            hrefTarget: '_self',
            hidden: connectedUser !== ''
        },
        '->'
    ]
});
