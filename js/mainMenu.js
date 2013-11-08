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
                        forceFit: true
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
            'news'
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