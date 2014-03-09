Ext.onReady(function() {
    Ext.create('Ext.button.Button', {
        renderTo: Ext.get('bouton_modif_equipe'),
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
                title: "Modification de l'�quipe",
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
                            fieldLabel: 'T�l�phone 1',
                            name: 'telephone_1'
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: 'T�l�phone 2',
                            name: 'telephone_2'
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: 'Email',
                            name: 'email'
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: 'R�ception le',
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
            windowModifEquipe.show();
            storeMonEquipe.load({
                callback: function(records, operation, success) {
                    windowModifEquipe.down('form').getForm().loadRecord(records[0]);
                }
            });
        }
    });
});

