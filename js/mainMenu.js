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
                    {text: 'Localisation des Gymnases', handler: function() {
                            Ext.define('Equipes', {
                                extend: 'Ext.data.Model',
                                fields: [
                                    'id_equipe',
                                    'gymnase',
                                    'localisation'
                                ]
                            });
                            Ext.create('Ext.data.Store', {
                                model: 'Equipes',
                                proxy: {
                                    type: 'ajax',
                                    url: 'ajax/details_equipes.php',
                                    reader: {
                                        type: 'json',
                                        root: 'results'
                                    }
                                },
                                autoLoad: true,
                                listeners: {
                                    load: function(store, records) {
                                        var markers = [];
                                        Ext.each(records, function(record) {
                                            var latLongStrings = record.get('localisation').split(',');
                                            if (latLongStrings.length === 2) {
                                                var lat = parseFloat(latLongStrings[0]);
                                                var long = parseFloat(latLongStrings[1]);
                                                markers.push({
                                                    lat: lat,
                                                    lng: long,
                                                    title: record.get('gymnase'),
                                                    listeners: {
                                                        click: function(e) {
                                                            var markerInsance = this;
                                                            var storeEquipes = Ext.create('Ext.data.Store', {
                                                                fields: [
                                                                    'id_equipe',
                                                                    'nom_equipe'
                                                                ],
                                                                proxy: {
                                                                    type: 'ajax',
                                                                    url: 'ajax/equipes.php',
                                                                    reader: {
                                                                        type: 'json',
                                                                        root: 'results'
                                                                    }
                                                                },
                                                                autoLoad: false
                                                            });
                                                            storeEquipes.load(function() {
                                                                var rec = storeEquipes.findRecord('id_equipe', record.get('id_equipe'));
                                                                var infowindow = new google.maps.InfoWindow({
                                                                    content: '<h3>Equipe : </h3>' + rec.get('nom_equipe') + '<br>' +
                                                                            '<h3>Gymnase : </h3>' + record.get('gymnase') + '<br>' +
                                                                            '<h3>Lien Google Maps : </h3><a href=\"http://maps.google.com/maps?z=12&t=m&q=loc:' + record.get('localisation') + '\" target=\"_blank\">Cliquez ici</a>'
                                                                });
                                                                infowindow.open(markerInsance.map, markerInsance);
                                                            }
                                                            );
                                                        }
                                                    }
                                                });
                                            }
                                        });
                                        Ext.create('Ext.window.Window', {
                                            title: 'Localisation des Gymnases',
                                            maximizable: true,
                                            modal: true,
                                            width: 800,
                                            height: 500,
                                            layout: 'fit',
                                            items: [
                                                {
                                                    xtype: 'gmappanel',
                                                    width: '100%',
                                                    height: 500,
                                                    mapOptions: {
                                                        zoom: 10,
                                                        mapTypeId: google.maps.MapTypeId.ROADMAP
                                                    },
                                                    center: {
                                                        geoCodeAddr: 'Aix en provence'
                                                    },
                                                    markers: markers
                                                }
                                            ]
                                        }).show();
                                    }
                                }
                            });
                        }},
                    {text: 'La Commission', handler: function() {
                            window.open('commission.php', '_self', false);
                        }},
                    {text: 'Ecrire au Webmaster', handler: function() {
                            window.open('mailto:benallemand@gmail.com');
                        }}
                ]
            },
            {
                text: 'Documents',
                menu: [
                    {text: 'Infos Utiles', handler: function() {
                            window.open('index_infos_utiles.php', '_self', false);
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
                    },
                    {
                        text: 'Règlements',
                        menu: [
                            {
                                text: 'Général',
                                handler: function() {
                                    window.open('reglements/ReglementGeneral.pdf', '_blank');
                                }
                            },
                            {
                                text: 'Féminin',
                                handler: function() {
                                    window.open('reglements/ReglementFeminin.pdf', '_blank');
                                }
                            },
                            {
                                text: 'Masculin',
                                handler: function() {
                                    window.open('reglements/ReglementMasculin.pdf', '_blank');
                                }
                            },
                            {
                                text: 'Koury Hanna',
                                handler: function() {
                                    window.open('reglements/ReglementKouryHanna.pdf', '_blank');
                                }
                            },
                            {
                                text: 'Isoardi',
                                handler: function() {
                                    window.open('reglements/ReglementIsoardi.pdf', '_blank');
                                }
                            }
                        ]
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
    Ext.define('Ext.form.PasswordField', {
        extend: 'Ext.form.field.Text',
        alias: 'widget.passwordfield',
        inputType: 'password'
    });
    var getAdminButton = function(tableName, records) {
        return {
            text: tableName,
            handler: function() {
                var columns = [];
                var fields = [];
                Ext.each(records, function(record) {
                    switch (record.get('Field')) {
                        case 'id_equipe':
                        case 'id_equipe_dom':
                        case 'id_equipe_ext':
                            if (record.get('Key') === 'PRI') {
                                fields.push(
                                        {
                                            name: record.get('Field'),
                                            type: 'int'
                                        }
                                );
                                columns.push({
                                    xtype: 'numbercolumn',
                                    format: '0',
                                    header: record.get('Field'),
                                    dataIndex: record.get('Field'),
                                    editor: 'numberfield'
                                });
                                return;
                            }
                            fields.push(
                                    {
                                        name: record.get('Field'),
                                        type: 'int'
                                    }
                            );
                            var storeEquipes = Ext.create('Ext.data.Store', {
                                fields: [
                                    {
                                        name: 'id_equipe',
                                        type: 'int'
                                    },
                                    'nom_equipe'
                                ],
                                proxy: {
                                    type: 'rest',
                                    url: 'ajax/equipes.php',
                                    reader: {
                                        type: 'json',
                                        root: 'results'
                                    }
                                },
                                autoLoad: true
                            });
                            columns.push({
                                header: record.get('Field'),
                                dataIndex: record.get('Field'),
                                editor: {
                                    xtype: 'combo',
                                    displayField: 'nom_equipe',
                                    valueField: 'id_equipe',
                                    queryMode: 'local',
                                    store: storeEquipes
                                },
                                renderer: function(val) {
                                    var index = storeEquipes.findExact('id_equipe', val);
                                    if (index !== -1) {
                                        var rs = storeEquipes.getAt(index).data;
                                        return rs.nom_equipe;
                                    }
                                }
                            });
                            return;
                        case 'chemin_image' :
                            fields.push(record.get('Field'));
                            columns.push({
                                header: record.get('Field'),
                                dataIndex: record.get('Field'),
                                renderer: function(val) {
                                    return '<img src="' + val + '">';
                                }
                            });
                            return;
                        default :
                            break;
                    }
                    switch (record.get('Type')) {
                        case 'smallint(3)' :
                        case 'tinyint(2)' :
                            fields.push(
                                    {
                                        name: record.get('Field'),
                                        type: 'int'
                                    }
                            );
                            columns.push({
                                xtype: 'numbercolumn',
                                format: '0',
                                header: record.get('Field'),
                                dataIndex: record.get('Field'),
                                editor: 'numberfield'
                            });
                            break;
                        case 'tinyint(1)' :
                            fields.push(
                                    {
                                        name: record.get('Field'),
                                        type: 'int'
                                    }
                            );
                            columns.push({
                                xtype: 'numbercolumn',
                                format: '0',
                                header: record.get('Field'),
                                dataIndex: record.get('Field'),
                                editor: 'numberfield'
                            });
                            break;
                        case 'date' :
                            fields.push(
                                    {
                                        name: record.get('Field'),
                                        type: 'date',
                                        dateFormat: 'd/m/Y'
                                    }
                            );
                            columns.push({
                                xtype: 'datecolumn',
                                format: 'd/m/Y',
                                header: record.get('Field'),
                                dataIndex: record.get('Field'),
                                editor: 'datefield'
                            });
                            break;
                        default :
                            fields.push(record.get('Field'));
                            if (record.get('Field') === 'password') {
                                columns.push({
                                    header: record.get('Field'),
                                    dataIndex: record.get('Field'),
                                    editor: 'passwordfield',
                                    renderer: function() {
                                        return 'Edit...';
                                    }
                                });
                            }
                            else {
                                columns.push({
                                    header: record.get('Field'),
                                    dataIndex: record.get('Field'),
                                    editor: 'textfield'
                                });
                            }
                            break;
                    }
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
                        selType: 'rowmodel',
                        plugins: [
                            Ext.create('Ext.grid.plugin.RowEditing', {
                                clicksToEdit: 2
                            })
                        ],
                        store: Ext.create('Ext.data.Store', {
                            fields: fields,
                            proxy: {
                                type: 'rest',
                                url: 'ajax/' + tableName + '.php',
                                reader: {
                                    type: 'json',
                                    root: 'results'
                                },
                                writer: {
                                    type: 'json'
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
                            autoLoad: true,
                            autoSync: true
                        }),
                        columns: columns,
                        dockedItems: [
                            {
                                xtype: 'toolbar',
                                dock: 'top',
                                defaults: {
                                    scale: 'medium'
                                },
                                items: [
                                    {
                                        icon: 'images/ajout.gif',
                                        text: 'Ajouter',
                                        tooltip: 'Ajouter',
                                        handler: function(button) {
                                            button.up('grid').getStore().insert(0, button.up('grid').getStore().getProxy().getModel());
                                            var formFields = [];
                                            Ext.each(button.up('grid').getStore().getProxy().getModel().getFields(), function(field) {
                                                var formField = {
                                                    xtype: 'textfield',
                                                    fieldLabel: field.name,
                                                    name: field.name
                                                };
                                                switch (field.name) {
                                                    case 'id_equipe' :
                                                        formField = {
                                                            xtype: 'combo',
                                                            queryMode: 'local',
                                                            fieldLabel: field.name,
                                                            name: field.name,
                                                            displayField: 'nom_equipe',
                                                            valueField: 'id_equipe',
                                                            store: Ext.create('Ext.data.Store', {
                                                                fields: [
                                                                    {
                                                                        name: 'id_equipe',
                                                                        type: 'int'
                                                                    },
                                                                    'nom_equipe'
                                                                ],
                                                                proxy: {
                                                                    type: 'rest',
                                                                    url: 'ajax/equipes.php',
                                                                    reader: {
                                                                        type: 'json',
                                                                        root: 'results'
                                                                    }
                                                                },
                                                                autoLoad: true
                                                            })

                                                        };
                                                        formFields.push(formField);
                                                        return;
                                                }
                                                switch (field.type.type) {
                                                    case 'int' :
                                                        formField = {
                                                            xtype: 'numberfield',
                                                            fieldLabel: field.name,
                                                            name: field.name
                                                        };
                                                        break;
                                                    case 'date' :
                                                        formField = {
                                                            xtype: 'datefield',
                                                            fieldLabel: field.name,
                                                            dateFormat: field.dateFormat,
                                                            name: field.name
                                                        };
                                                        break;
                                                }
                                                formFields.push(formField);
                                            });
                                            var windowCreate = Ext.create('Ext.window.Window', {
                                                title: 'Ajout',
                                                height: 500,
                                                width: 700,
                                                layout: 'fit',
                                                items: [
                                                    {
                                                        xtype: 'form',
                                                        layout: 'anchor',
                                                        autoScroll: true,
                                                        defaults: {
                                                            anchor: '90%',
                                                            margin: 10
                                                        },
                                                        items: formFields
                                                    }
                                                ]
                                            });
                                            windowCreate.down('form').getForm().loadRecord(button.up('grid').getStore().getAt(0));
                                            windowCreate.show();
                                        }
                                    },
                                    {
                                        icon: 'images/delete.gif',
                                        tooltip: 'Supprimer',
                                        text: 'Supprimer',
                                        handler: function(button) {
                                            var rec = button.up('grid').getView().getSelectionModel().getSelection()[0];
                                            Ext.Msg.show({
                                                title: 'Effacer ?',
                                                msg: 'Confirmez vous la suppression ?',
                                                buttons: Ext.Msg.OKCANCEL,
                                                icon: Ext.Msg.QUESTION,
                                                fn: function(btn) {
                                                    if (btn === 'ok') {
                                                        button.up('grid').getStore().remove(rec);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                ]
                            }
                        ]
                    }
                }).show();
            }
        };
    };
    getGenericColumnStore = function(tableName) {
        return Ext.create('Ext.data.Store', {
            fields: [
                'Field',
                'Type',
                'Null',
                'Key',
                'Default',
                'Extra'
            ],
            proxy: {
                type: 'rest',
                url: 'ajax/' + tableName + '.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            }
        });
    };
    var initMenuAdmin = function() {
        var menuAdmin = Ext.ComponentQuery.query('menuitem[text=Admin] > menu')[0];
        var tableNames = [
            'classements',
            'competitions',
            'comptes_acces',
            'dates_limite',
            'details_equipes',
            'equipes',
            'images',
            'journees',
            'matches',
            'news',
            'joueurs'
        ];
        Ext.each(tableNames, function(tableName) {
            var store = getGenericColumnStore(tableName);
            store.load({
                params: {
                    GET_COLUMNS: true
                },
                callback: function(records, operation, success) {
                    menuAdmin.add(getAdminButton(tableName, records));
                }
            });
        });
    };
    Ext.Ajax.request({
        url: 'ajax/getSessionRights.php',
        success: function(response) {
            var responseJson = Ext.decode(response.responseText);
            if (responseJson.message === 'admin') {
                var menuPortailEquipes = Ext.ComponentQuery.query('button[text=Portail Equipes] > menu')[0];
                menuPortailEquipes.add({
                    text: 'Admin',
                    menu: []
                });
                initMenuAdmin();
            }
        }
    });
});