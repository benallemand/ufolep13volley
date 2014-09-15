Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: ['Administration'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
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
                                handler: function() {
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
                                text: 'Gestion des gymnases',
                                action: 'manageGymnasiums'
                            }
                        ]
                    },
                    {
                        region: 'center',
                        xtype: 'tabpanel',
                        activeTab: 0,
                        items: {
                            title: 'Panneau Principal',
                            layout: 'fit'
                        }
                    }
                ]
            }
        });
        var getAdminButton = function(tableName, records) {
            return {
                text: tableName,
                handler: function() {
                    var columns = [];
                    var fields = [];
                    Ext.each(records, function(record) {
                        switch (record.get('Field')) {
                            case 'id_club':
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
                                var storeClubs = Ext.create('Ext.data.Store', {
                                    fields: [
                                        {
                                            name: 'id',
                                            type: 'int'
                                        },
                                        'nom'
                                    ],
                                    proxy: {
                                        type: 'rest',
                                        url: 'ajax/clubs.php',
                                        reader: {
                                            type: 'json',
                                            root: 'results'
                                        },
                                        pageParam: undefined,
                                        startParam: undefined,
                                        limitParam: undefined
                                    },
                                    autoLoad: true
                                });
                                columns.push({
                                    header: record.get('Field'),
                                    dataIndex: record.get('Field'),
                                    editor: {
                                        xtype: 'combo',
                                        displayField: 'nom',
                                        valueField: 'id',
                                        queryMode: 'local',
                                        store: storeClubs
                                    },
                                    renderer: function(val) {
                                        var index = storeClubs.findExact('id', val);
                                        if (index !== -1) {
                                            var rs = storeClubs.getAt(index).data;
                                            return rs.nom;
                                        }
                                    }
                                });
                                return;
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
                                        },
                                        pageParam: undefined,
                                        startParam: undefined,
                                        limitParam: undefined
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
                                        return '<img src="' + val + '" width="150px" height="100px">';
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
                    var store = Ext.create('Ext.data.Store', {
                        fields: fields,
                        pageSize: 25,
                        proxy: {
                            type: 'rest',
                            url: 'ajax/' + tableName + '.php',
                            reader: {
                                type: 'json',
                                root: 'results',
                                totalProperty: 'totalCount'
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
                    });
                    var mainPanel = Ext.ComponentQuery.query('panel[title=Panneau Principal]')[0];
                    mainPanel.removeAll();
                    mainPanel.add({
                        title: tableName,
                        xtype: 'grid',
                        autoScroll: true,
                        selType: 'rowmodel',
                        plugins: [
                            Ext.create('Ext.grid.plugin.RowEditing', {
                                clicksToEdit: 2
                            })
                        ],
                        store: store,
                        columns: {
                            items: columns,
                            defaults: {
                                flex: 1
                            }
                        },
                        dockedItems: [
                            {
                                xtype: 'toolbar',
                                dock: 'top',
                                defaults: {
                                    scale: 'medium'
                                },
                                items: [
                                    {
                                        xtype: 'textfield',
                                        fieldLabel: 'Recherche',
                                        listeners: {
                                            change: function(textfield, newValue, oldValue) {
                                                var store = textfield.up('grid').getStore();
                                                store.load({
                                                    params: {
                                                        query: newValue
                                                    }
                                                });
                                            }
                                        }
                                    },
                                    '->',
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
                            },
                            {
                                xtype: 'pagingtoolbar',
                                dock: 'bottom',
                                store: store,
                                displayInfo: true,
                                displayMsg: 'Affichage des éléments {0} - {1} sur {2}',
                                emptyMsg: "Rien à afficher"
                            }
                        ]
                    });
                }
            };
        };
        var getGenericColumnStore = function(tableName) {
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
            var menuAdmin = Ext.ComponentQuery.query('panel[title=Navigation]')[0];
            var tableNames = [
                'classements',
                'competitions',
                'dates_limite',
                'details_equipes',
                'equipes',
                'journees',
                'matches',
                'clubs'
            ];
            menuAdmin.add({
                text: 'Indicateurs',
                handler: function() {
                    var mainPanel = Ext.ComponentQuery.query('panel[title=Panneau Principal]')[0];
                    mainPanel.removeAll();
                    mainPanel.setAutoScroll(true);
                    mainPanel.add({
                        title: 'Indicateurs',
                        layout: {
                            type: 'vbox',
                            align: 'stretch'
                        },
                        autoScroll: true,
                        items: []
                    });
                    var storeIndicators = Ext.create('Ext.data.Store', {
                        fields: [
                            'fieldLabel',
                            'value',
                            'details'
                        ],
                        proxy: {
                            type: 'rest',
                            url: 'ajax/indicators.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        }
                    });
                    storeIndicators.load({
                        callback: function(records, operation, success) {
                            Ext.each(records, function(record) {
                                var detailsData = record.get('details');
                                var fields = [];
                                var columns = [];
                                for (var k in detailsData[0]) {
                                    fields.push(k);
                                    columns.push({
                                        header: k,
                                        dataIndex: k,
                                        flex: 1
                                    });
                                }
                                var indicatorPanel = Ext.ComponentQuery.query('panel[title=Indicateurs]')[0];
                                if (record.get('value') === 0) {
                                    return;
                                }
                                indicatorPanel.add(
                                        {
                                            layout: 'border',
                                            height: 300,
                                            items: [
                                                {
                                                    layout: 'fit',
                                                    region: 'west',
                                                    title: record.get('fieldLabel'),
                                                    flex: 1,
                                                    items: {
                                                        xtype: 'displayfield',
                                                        fieldLabel: '',
                                                        hideLabel: true,
                                                        value: record.get('value')
                                                    }
                                                },
                                                {
                                                    region: 'center',
                                                    flex: 7,
                                                    xtype: 'grid',
                                                    autoScroll: true,
                                                    store: Ext.create('Ext.data.Store', {
                                                        fields: fields,
                                                        data: {
                                                            'items': detailsData
                                                        },
                                                        proxy: {
                                                            type: 'memory',
                                                            reader: {
                                                                type: 'json',
                                                                root: 'items'
                                                            }
                                                        }
                                                    }),
                                                    columns: columns
                                                }
                                            ]
                                        });
                            });
                        }
                    });
                }
            });
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
        initMenuAdmin();
    }
});