Ext.onReady(function() {
//    Ext.define("plugin.Printer", {
//        statics: {
//            print: function(htmlElement, printAutomatically) {
//                var win = window.open('', 'Print Panel');
//                win.document.open();
//                win.document.write(htmlElement.outerHTML);
//                win.document.close();
//                if (printAutomatically) {
//                    win.print();
//                }
//                if (this.closeAutomaticallyAfterPrint) {
//                    if (Ext.isIE) {
//                        window.close();
//                    } else {
//                        win.close();
//                    }
//                }
//            }
//        }
//    });
    Ext.create('Ext.panel.Panel', {
        layout: 'hbox',
        renderTo: Ext.get('bouton_modif_equipe'),
        defaults: {
            margins: 10
        },
        items: [
            {
                xtype: 'button',
                text: 'Afficher la fiche équipe',
                handler: function() {
                    var windowTeamSheet = Ext.create('Ext.window.Window', {
                        title: 'Fiche équipe',
                        height: 600,
                        width: 800,
                        modal: true,
                        layout: 'fit',
//                        dockedItems: [
//                            {
//                                xtype: 'toolbar',
//                                dock: 'top',
//                                items: [
//                                    {
//                                        text: 'Imprimer',
//                                        handler: function() {
//                                            var html = Ext.dom.Query.selectNode('#myPanelId-body');
//                                            plugin.Printer.print(html, true);
//                                        }
//                                    }
//                                ]
//                            }
//                        ],
                        items: {
                            xtype: 'form',
//                            id: 'myPanelId',
                            layout: 'border',
                            items: [
                                {
                                    region: 'north',
                                    layout: 'border',
                                    flex: 1,
                                    items: [
                                        {
                                            region: 'west',
                                            flex: 1,
                                            layout: 'anchor',
                                            autoScroll: true,
                                            defaults: {
                                                anchor: '90%'
                                            },
                                            items: [
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Club',
                                                    name: 'club'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Championnat',
                                                    name: 'championnat'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Division',
                                                    name: 'division'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Capitaine',
                                                    name: 'capitaine'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Portable',
                                                    name: 'portable'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Courriel',
                                                    name: 'courriel'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Créneau',
                                                    name: 'creneau'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Gymnase',
                                                    name: 'gymnase'
                                                }
                                            ]
                                        },
                                        {
                                            region: 'center',
                                            autoScroll: true,
                                            flex: 1,
                                            layout: {
                                                type: 'vbox',
                                                align: 'center'
                                            },
                                            items: [
                                                {
                                                    width: 100,
                                                    height: 100,
                                                    xtype: 'image',
                                                    src: 'images/Ufolep13Volley2.jpg'
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    hideLabel: true,
                                                    name: 'equipe',
                                                    flex: 1
                                                },
                                                {
                                                    xtype: 'displayfield',
                                                    fieldLabel: 'Visa C.T.S.D. le',
                                                    name: 'date_visa_ctsd',
                                                    flex: 1
                                                }
                                            ]
                                        },
                                        {
                                            region: 'east',
                                            autoScroll: true,
                                            flex: 1,
                                            layout: 'border',
                                            items: [
                                                {
                                                    region: 'north',
                                                    layout: {
                                                        type: 'vbox',
                                                        align: 'center'
                                                    },
                                                    items: [
                                                        {
                                                            width: 100,
                                                            height: 100,
                                                            xtype: 'image',
                                                            src: 'images/MainVolley.jpg'
                                                        },
                                                        {
                                                            width: 100,
                                                            height: 20,
                                                            xtype: 'image',
                                                            src: 'images/JeuAvantEnjeu.jpg'
                                                        }
                                                    ]
                                                },
                                                {
                                                    region: 'center',
                                                    flex: 1,
                                                    layout: 'anchor',
                                                    defaults: {
                                                        anchor: '90%'
                                                    },
                                                    items: [
                                                        {
                                                            xtype: 'displayfield',
                                                            fieldLabel: 'Le',
                                                            name: 'date_match',
                                                            value: '...............'
                                                        },
                                                        {
                                                            xtype: 'displayfield',
                                                            fieldLabel: 'Nombre de joueurs présents',
                                                            name: 'nb_joueurs',
                                                            value: '...............'
                                                        },
                                                        {
                                                            xtype: 'displayfield',
                                                            fieldLabel: 'Nombre de joueuses présentes',
                                                            name: 'nb_joueuses',
                                                            value: '...............'
                                                        },
                                                        {
                                                            xtype: 'displayfield',
                                                            fieldLabel: 'Equipe adverse',
                                                            name: 'equipe_adverse',
                                                            value: '...............'
                                                        }
                                                    ]
                                                }
                                            ]
                                        }
                                    ]
                                },
                                {
                                    region: 'center',
                                    autoScroll: true,
                                    title: 'Joueurs',
                                    flex: 2,
                                    layout: {
                                        type: 'table',
                                        columns: 3
                                    },
                                    items: []
                                }
                            ]
                        }
                    });
                    windowTeamSheet.show();
                    var storeTeamSheet = Ext.create('Ext.data.Store', {
                        fields: [
                            'club',
                            'championnat',
                            'division',
                            'capitaine',
                            'portable',
                            'courriel',
                            'creneau',
                            'gymnase',
                            'equipe',
                            'date_visa_ctsd'
                        ],
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getMyTeamSheet.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        },
                        autoLoad: false
                    });
                    storeTeamSheet.load(function(records) {
                        var form = windowTeamSheet.down('form');
                        if (records.length !== 1) {
                            return;
                        }
                        var record = records[0];
                        form.loadRecord(record);
                    });
                    var storeMyPlayers = Ext.create('Ext.data.Store', {
                        fields: [
                            'full_name',
                            'prenom',
                            'nom',
                            'telephone',
                            'email',
                            'num_licence',
                            'path_photo',
                            'sexe',
                            {
                                name: 'departement_affiliation',
                                type: 'int'
                            },
                            {
                                name: 'est_actif',
                                type: 'bool'
                            },
                            {
                                name: 'id_club',
                                type: 'int'
                            },
                            'adresse',
                            'code_postal',
                            'ville',
                            'telephone2',
                            'email2',
                            'telephone3',
                            'telephone4',
                            {
                                name: 'est_licence_valide',
                                type: 'bool'
                            },
                            {
                                name: 'est_responsable_club',
                                type: 'bool'
                            },
                            {
                                name: 'est_capitaine',
                                type: 'bool'
                            },
                            {
                                name: 'id',
                                type: 'int'
                            },
                            {
                                name: 'date_homologation',
                                type: 'date'
                            }
                        ],
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getMyPlayers.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        },
                        autoLoad: false
                    });
                    storeMyPlayers.load(function(records) {
                        var panelPlayers = windowTeamSheet.down('panel[title=Joueurs]');
                        Ext.each(records, function(record) {
                            panelPlayers.add({
                                layout: 'border',
                                width: 250,
                                height: 150,
                                items: [
                                    {
                                        region: 'west',
                                        xtype: 'image',
                                        src: record.get('path_photo'),
                                        height: 100,
                                        width: 100
                                    },
                                    {
                                        region: 'center',
                                        layout: 'anchor',
                                        defaults: {
                                            anchor: '90%'
                                        },
                                        items: [
                                            {
                                                xtype: 'displayfield',
                                                fieldLabel: 'Prenom',
                                                hideLabel: true,
                                                value: record.get('prenom')
                                            },
                                            {
                                                xtype: 'displayfield',
                                                fieldLabel: 'Nom',
                                                hideLabel: true,
                                                value: record.get('nom')
                                            },
                                            {
                                                xtype: 'displayfield',
                                                fieldLabel: 'Numéro de licence et Genre',
                                                hideLabel: true,
                                                value: record.get('num_licence') + ' /' + record.get('sexe')
                                            },
                                            {
                                                xtype: 'checkbox',
                                                boxLabel: 'Présent'
                                            },
                                            {
                                                xtype: 'checkbox',
                                                boxLabel: 'Capitaine',
                                                checked: record.get('est_capitaine'),
                                                readOnly: true,
                                                hidden: !record.get('est_capitaine')
                                            }
                                        ]
                                    }
                                ]
                            });
                        });
                    });
                }
            },
            {
                xtype: 'button',
                text: 'Gestions des joueurs/joueuses',
                handler: function() {
                    var storeMyPlayers = Ext.create('Ext.data.Store', {
                        fields: [
                            'full_name',
                            'prenom',
                            'nom',
                            'telephone',
                            'email',
                            'num_licence',
                            'path_photo',
                            'sexe',
                            {
                                name: 'departement_affiliation',
                                type: 'int'
                            },
                            {
                                name: 'est_actif',
                                type: 'bool'
                            },
                            {
                                name: 'id_club',
                                type: 'int'
                            },
                            'adresse',
                            'code_postal',
                            'ville',
                            'telephone2',
                            'email2',
                            'telephone3',
                            'telephone4',
                            {
                                name: 'est_licence_valide',
                                type: 'bool'
                            },
                            {
                                name: 'est_responsable_club',
                                type: 'bool'
                            },
                            {
                                name: 'est_capitaine',
                                type: 'bool'
                            },
                            {
                                name: 'id',
                                type: 'int'
                            },
                            {
                                name: 'date_homologation',
                                type: 'date'
                            }
                        ],
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getMyPlayers.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        },
                        autoLoad: true
                    });
                    var windowManagePlayers = Ext.create('Ext.window.Window', {
                        title: 'Gestion des joueurs/joueuses',
                        height: 400,
                        width: 700,
                        modal: true,
                        layout: 'fit',
                        items: {
                            xtype: 'grid',
                            store: storeMyPlayers,
                            columns: {
                                items: [
                                    {
                                        header: 'Prénom',
                                        dataIndex: 'prenom'
                                    },
                                    {
                                        header: 'Nom',
                                        dataIndex: 'nom'
                                    },
                                    {
                                        header: 'Numéro de licence',
                                        dataIndex: 'num_licence'
                                    },
                                    {
                                        header: 'Capitaine ?',
                                        dataIndex: 'est_capitaine',
                                        xtype: 'checkcolumn',
                                        listeners: {
                                            beforecheckchange: function() {
                                                return false;
                                            }
                                        }
                                    },
                                    {
                                        header: 'Photo',
                                        dataIndex: 'path_photo',
                                        width: 150,
                                        flex: null,
                                        renderer: function(value, meta, record) {
                                            return '<img width="100" src="' + record.get('path_photo') + '" />';
                                        }
                                    },
                                    {
                                        header: 'Gestion',
                                        xtype: 'actioncolumn',
                                        width: 100,
                                        flex: null,
                                        items: [
                                            {
                                                icon: 'images/delete.gif',
                                                handler: function(grid, rowIndex) {
                                                    var rec = grid.getStore().getAt(rowIndex);
                                                    Ext.Msg.show({
                                                        title: 'Retirer un joueur',
                                                        msg: 'Voulez-vous retirer ' + rec.get('prenom') + ' ' + rec.get('nom') + ' de votre équipe ?',
                                                        buttons: Ext.Msg.OKCANCEL,
                                                        icon: Ext.Msg.QUESTION,
                                                        fn: function(btn) {
                                                            if (btn === 'ok') {
                                                                Ext.Ajax.request({
                                                                    url: 'ajax/removePlayerFromMyTeam.php',
                                                                    params: {
                                                                        id: rec.get('id')
                                                                    },
                                                                    success: function(response) {
                                                                        var responseJson = Ext.decode(response.responseText);
                                                                        Ext.Msg.alert('Info', responseJson.message);
                                                                        storeMyPlayers.load();
                                                                    }
                                                                });
                                                            }
                                                        }
                                                    });
                                                }
                                            }
                                        ]
                                    }
                                ],
                                defaults: {
                                    flex: 1
                                }
                            },
                            dockedItems: [
                                {
                                    xtype: 'toolbar',
                                    dock: 'top',
                                    items: [
                                        {
                                            xtype: 'button',
                                            text: 'Ajouter un joueur',
                                            handler: function() {
                                                var storePlayers = Ext.create('Ext.data.Store', {
                                                    fields: [
                                                        'full_name',
                                                        'prenom',
                                                        'nom',
                                                        'telephone',
                                                        'email',
                                                        'num_licence',
                                                        'path_photo',
                                                        'sexe',
                                                        {
                                                            name: 'departement_affiliation',
                                                            type: 'int'
                                                        },
                                                        {
                                                            name: 'est_actif',
                                                            type: 'bool'
                                                        },
                                                        {
                                                            name: 'id_club',
                                                            type: 'int'
                                                        },
                                                        'adresse',
                                                        'code_postal',
                                                        'ville',
                                                        'telephone2',
                                                        'email2',
                                                        'telephone3',
                                                        'telephone4',
                                                        {
                                                            name: 'est_licence_valide',
                                                            type: 'bool'
                                                        },
                                                        {
                                                            name: 'est_responsable_club',
                                                            type: 'bool'
                                                        },
                                                        {
                                                            name: 'id',
                                                            type: 'int'
                                                        },
                                                        {
                                                            name: 'date_homologation',
                                                            type: 'date'
                                                        }
                                                    ],
                                                    proxy: {
                                                        type: 'ajax',
                                                        url: 'ajax/getPlayers.php',
                                                        reader: {
                                                            type: 'json',
                                                            root: 'results'
                                                        }
                                                    },
                                                    autoLoad: true
                                                });
                                                var windowAddPlayerToMyTeam = Ext.create('Ext.window.Window', {
                                                    title: "Ajout d'un joueur",
                                                    height: 500,
                                                    width: 500,
                                                    modal: true,
                                                    layout: 'fit',
                                                    items: {
                                                        xtype: 'form',
                                                        layout: 'anchor',
                                                        defaults: {
                                                            anchor: '90%',
                                                            margins: 10
                                                        },
                                                        url: 'ajax/addPlayerToMyTeam.php',
                                                        items: [
                                                            {
                                                                xtype: 'combo',
                                                                forceSelection: true,
                                                                fieldLabel: 'Joueur',
                                                                name: 'id_joueur',
                                                                queryMode: 'local',
                                                                allowBlank: false,
                                                                store: storePlayers,
                                                                displayField: 'full_name',
                                                                valueField: 'id',
                                                                listeners: {
                                                                    select: function(combo, records) {
                                                                        combo.up('form').down('image').setSrc(records[0].get('path_photo'));
                                                                    }
                                                                }
                                                            },
                                                            {
                                                                xtype: 'image',
                                                                anchor: '50%',
                                                                margins: 10,
                                                                src: null
                                                            }
                                                        ],
                                                        buttons: [
                                                            {
                                                                text: 'Annuler',
                                                                handler: function() {
                                                                    this.up('window').close();
                                                                }
                                                            },
                                                            {
                                                                text: 'Sauver',
                                                                formBind: true,
                                                                disabled: true,
                                                                handler: function() {
                                                                    var button = this;
                                                                    var form = button.up('form').getForm();
                                                                    if (form.isValid()) {
                                                                        form.submit({
                                                                            success: function(form, action) {
                                                                                storeMyPlayers.load();
                                                                                windowAddPlayerToMyTeam.close();
                                                                            },
                                                                            failure: function(form, action) {
                                                                                Ext.Msg.alert('Erreur', action.result.message);
                                                                            }
                                                                        });
                                                                    }
                                                                }
                                                            }
                                                        ]
                                                    }
                                                });
                                                windowAddPlayerToMyTeam.show();
                                            }
                                        },
                                        {
                                            xtype: 'button',
                                            text: "Modifier le responsable d'équipe",
                                            handler: function() {
                                                var windowUpdateMyTeamCaptain = Ext.create('Ext.window.Window', {
                                                    title: "Modifier le responsable d'équipe",
                                                    height: 500,
                                                    width: 500,
                                                    modal: true,
                                                    layout: 'fit',
                                                    items: {
                                                        xtype: 'form',
                                                        layout: 'anchor',
                                                        defaults: {
                                                            anchor: '90%',
                                                            margins: 10
                                                        },
                                                        url: 'ajax/updateMyTeamCaptain.php',
                                                        items: [
                                                            {
                                                                xtype: 'combo',
                                                                forceSelection: true,
                                                                fieldLabel: 'Joueur',
                                                                name: 'id_joueur',
                                                                queryMode: 'local',
                                                                allowBlank: false,
                                                                store: this.up('grid').getStore(),
                                                                displayField: 'full_name',
                                                                valueField: 'id'
                                                            }
                                                        ],
                                                        buttons: [
                                                            {
                                                                text: 'Annuler',
                                                                handler: function() {
                                                                    this.up('window').close();
                                                                }
                                                            },
                                                            {
                                                                text: 'Sauver',
                                                                formBind: true,
                                                                disabled: true,
                                                                handler: function() {
                                                                    var button = this;
                                                                    var form = button.up('form').getForm();
                                                                    if (form.isValid()) {
                                                                        form.submit({
                                                                            success: function(form, action) {
                                                                                storeMyPlayers.load();
                                                                                windowUpdateMyTeamCaptain.close();
                                                                            },
                                                                            failure: function(form, action) {
                                                                                Ext.Msg.alert('Erreur', action.result.message);
                                                                            }
                                                                        });
                                                                    }
                                                                }
                                                            }
                                                        ]
                                                    }
                                                });
                                                windowUpdateMyTeamCaptain.show();
                                            }
                                        }
                                    ]
                                }
                            ]
                        }
                    });
                    windowManagePlayers.show();
                }
            },
            {
                xtype: 'button',
                text: 'Modifier les informations',
                handler: function() {
                    var storeMonEquipe = Ext.create('Ext.data.Store', {
                        fields: [
                            {
                                name: 'id_equipe',
                                type: 'int'
                            },
                            {
                                name: 'id_club',
                                type: 'int'
                            },
                            'responsable',
                            'telephone_1',
                            'telephone_2',
                            'email',
                            'gymnase',
                            'localisation',
                            'jour_reception',
                            'heure_reception',
                            'site_web',
                            'photo',
                            'fdm'
                        ],
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getMonEquipe.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            },
                            listeners: {
                                exception: function(proxy, response, operation) {
                                    var responseJson = Ext.decode(response.responseText);
                                    Ext.MessageBox.show({
                                        title: 'Erreur',
                                        msg: responseJson.message,
                                        icon: Ext.MessageBox.ERROR,
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            }
                        },
                        autoLoad: false
                    });
                    var storeClubs = Ext.create('Ext.data.Store', {
                        fields: [
                            {
                                name: 'id',
                                type: 'int'
                            },
                            'nom'
                        ],
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/clubs.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            },
                            listeners: {
                                exception: function(proxy, response, operation) {
                                    var responseJson = Ext.decode(response.responseText);
                                    Ext.MessageBox.show({
                                        title: 'Erreur',
                                        msg: responseJson.message,
                                        icon: Ext.MessageBox.ERROR,
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            }
                        },
                        autoLoad: true
                    });
                    var windowModifEquipe = Ext.create('Ext.window.Window', {
                        title: "Modification de l'équipe",
                        height: 400,
                        width: 700,
                        modal: true,
                        layout: 'fit',
                        items: {
                            xtype: 'form',
                            trackResetOnLoad: true,
                            layout: 'anchor',
                            defaults: {
                                anchor: '90%',
                                margins: 10
                            },
                            url: 'ajax/modifierMonEquipe.php',
                            items: [
                                {
                                    xtype: 'hidden',
                                    fieldLabel: 'id_equipe',
                                    name: 'id_equipe'
                                },
                                {
                                    xtype: 'combo',
                                    fieldLabel: 'Club',
                                    name: 'id_club',
                                    displayField: 'nom',
                                    valueField: 'id',
                                    store: storeClubs,
                                    queryMode: 'local'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Responsable',
                                    name: 'responsable'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Téléphone 1',
                                    name: 'telephone_1'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Téléphone 2',
                                    name: 'telephone_2'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Email',
                                    name: 'email'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Réception le',
                                    name: 'jour_reception'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Horaire',
                                    name: 'heure_reception'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Gymnase',
                                    name: 'gymnase'
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Localisation GPS',
                                    name: 'localisation',
                                    regex: /^\d+[\.]\d+,\d+[\.]\d+$/,
                                    regexText: "Merci d'utiliser le format Google Maps, par exemple : 43.410496,5.242646"
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Site web',
                                    name: 'site_web'
                                }
                            ],
                            buttons: [
                                {
                                    text: 'Annuler',
                                    handler: function() {
                                        this.up('window').close();
                                    }
                                },
                                {
                                    text: 'Sauver',
                                    formBind: true,
                                    disabled: true,
                                    handler: function() {
                                        var button = this;
                                        var form = button.up('form').getForm();
                                        if (form.isValid()) {
                                            var dirtyFieldsJson = form.getFieldValues(true);
                                            var dirtyFieldsArray = [];
                                            for (var key in dirtyFieldsJson) {
                                                dirtyFieldsArray.push(key);
                                            }
                                            form.submit({
                                                params: {
                                                    dirtyFields: dirtyFieldsArray.join(',')
                                                },
                                                success: function(form, action) {
                                                    window.location.reload();
                                                },
                                                failure: function(form, action) {
                                                    Ext.Msg.alert('Erreur', action.result.message);
                                                }
                                            });
                                        }
                                    }
                                }
                            ]
                        }
                    });
                    windowModifEquipe.show();
                    storeMonEquipe.load({
                        callback: function(records, operation, success) {
                            windowModifEquipe.down('form').getForm().loadRecord(records[0]);
                        }
                    });
                }
            },
            {
                xtype: 'button',
                text: 'Changer de mot de passe',
                handler: function() {
                    var windowModifMotDePasse = Ext.create('Ext.window.Window', {
                        title: "Modification du mot de passe",
                        height: 400,
                        width: 700,
                        modal: true,
                        layout: 'fit',
                        items: {
                            xtype: 'form',
                            layout: 'anchor',
                            defaults: {
                                anchor: '90%',
                                margins: 10
                            },
                            url: 'ajax/modifierMonMotDePasse.php',
                            items: [
                                {
                                    xtype: 'textfield',
                                    inputType: 'password',
                                    fieldLabel: 'Mot de passe',
                                    name: 'password',
                                    allowBlank: false
                                },
                                {
                                    xtype: 'textfield',
                                    inputType: 'password',
                                    fieldLabel: 'Mot de passe (vérification)',
                                    name: 'password2',
                                    allowBlank: false,
                                    validator: function(val) {
                                        if (val !== windowModifMotDePasse.down('form').getForm().findField('password').getValue()) {
                                            return 'Merci de saisir 2 fois le même mot de passe !';
                                        }
                                        return true;
                                    }
                                }
                            ],
                            buttons: [
                                {
                                    text: 'Annuler',
                                    handler: function() {
                                        this.up('window').close();
                                    }
                                },
                                {
                                    text: 'Sauver',
                                    formBind: true,
                                    disabled: true,
                                    handler: function() {
                                        var button = this;
                                        var form = button.up('form').getForm();
                                        if (form.isValid()) {
                                            form.submit({
                                                success: function(form, action) {
                                                    window.location.reload();
                                                },
                                                failure: function(form, action) {
                                                    Ext.Msg.alert('Erreur', action.result.message);
                                                }
                                            });
                                        }
                                    }
                                }
                            ]
                        }
                    });
                    windowModifMotDePasse.show();
                }
            }
        ]
    });
});


