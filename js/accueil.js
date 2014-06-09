Ext.application({
    requires: [],
    views: ['new.Grid', 'match.LastResultsGrid', 'forum.LastPostsGrid'],
    controllers: [],
    stores: ['News', 'LastResults', 'LastPosts'],
    models: ['New', 'LastResult', 'LastPost'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        var tools = {
            showMobileVersion: function() {
                location.href = 'index_mobile.html';
            },
            showNormalVersion: function() {
                afficheFormulaireNews = function(isUpdate) {
                    Ext.create('Ext.window.Window', {
                        title: 'News',
                        height: 400,
                        width: 700,
                        modal: true,
                        layout: 'fit',
                        items: {
                            xtype: 'form',
                            layout: 'anchor',
                            defaults: {
                                anchor: '100%',
                                margins: 10
                            },
                            items: [
                                {
                                    xtype: 'datefield',
                                    fieldLabel: 'Date',
                                    name: 'date_news',
                                    format: 'd/m/Y',
                                    value: new Date()
                                },
                                {
                                    xtype: 'textfield',
                                    fieldLabel: 'Titre',
                                    name: 'titre_news'
                                },
                                {
                                    xtype: 'textarea',
                                    fieldLabel: 'Texte',
                                    name: 'texte_news'
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
                                        var form = this.up('form').getForm();
                                        if (form.isValid()) {
                                            if (isUpdate) {
                                                form.updateRecord();
                                            }
                                            else {
                                                var newNews = Ext.create('News', form.getValues());
                                                Ext.ComponentQuery.query('grid[title=Quelques news...]')[0].getStore().insert(0, newNews);
                                            }
                                            this.up('window').close();
                                        }
                                    }
                                }
                            ]
                        }
                    }).show();
                    if (isUpdate) {
                        var rec = Ext.ComponentQuery.query('grid[title=Quelques news...]')[0].getView().getSelectionModel().getSelection()[0];
                        if (rec) {
                            Ext.ComponentQuery.query('window[title=News] > form')[0].getForm().loadRecord(rec);
                        }
                        else {
                            Ext.ComponentQuery.query('window[title=News]')[0].close();
                        }
                    }
                };
                Ext.tip.QuickTipManager.init();
                Ext.Ajax.request({
                    url: 'ajax/getSessionRights.php',
                    success: function(response) {
                        var responseJson = Ext.decode(response.responseText);
                        if (responseJson.message === 'admin') {
                            var toolbars = Ext.ComponentQuery.query('grid[title=Quelques news...] > toolbar');
                            toolbars[0].show();
                        }
                    }
                });
                var changingImage = Ext.create('Ext.Img', {
                    src: ''
                });
                Ext.Ajax.request({
                    url: 'ajax/getVolleyballImages.php',
                    success: function(response) {
                        var responseJson = Ext.decode(response.responseText);
                        var photos = responseJson.photos.photo;
                        photos = Ext.Array.filter(photos, function(item) {
                            return (!item.url_c ? false : true);
                        });
                        var photo = photos[Ext.Number.randomInt(0, photos.length - 1)];
                        changingImage.setSrc(photo.url_c);
                        var task = {
                            run: function() {
                                var photo = photos[Ext.Number.randomInt(0, photos.length - 1)];
                                if (photo.url_c) {
                                    changingImage.setSrc(photo.url_c);
                                    changingImage.setWidth(400);
                                }
                                else {
                                    console.log(photo);
                                }
                            },
                            interval: 3000
                        };
                        Ext.TaskManager.start(task);
                    }
                });
                Ext.create('Ext.panel.Panel', {
                    layout: 'border',
                    width: 1000,
                    height: 960,
                    renderTo: Ext.get('accueil'),
                    items: [
                        {
                            region: 'north',
                            flex: 1,
                            layout: 'border',
                            items: [
                                {
                                    region: 'west',
                                    xtype: 'NewGrid'
                                },
                                {
                                    xtype: 'panel',
                                    flex: 1,
                                    region: 'center',
                                    items: [
                                        changingImage
                                    ]
                                }
                            ]
                        },
                        {
                            region: 'center',
                            flex: 2,
                            layout: 'border',
                            items: [
                                {
                                    region: 'north',
                                    xtype: 'LastResultsGrid'
                                },
                                {
                                    region: 'center',
                                    xtype: 'LastPostsGrid'
                                }
                            ]
                        }
                    ]
                });
            }
        };
        if (Ext.is.Phone || Ext.is.Tablet) {
            Ext.Msg.show({
                title: 'Voulez-vous accéder à la version mobile du site ?',
                msg: 'Vous naviguez sur un téléphone ou une tablette, voulez-vous accéder à la version mobile du site ?',
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function(btn) {
                    if (btn === 'no') {
                        tools.showNormalVersion();
                        return;
                    }
                    tools.showMobileVersion();
                }
            });
        }
        else {
            tools.showNormalVersion();
        }
    }
});