Ext.onReady(function() {
    var tools = {
        showMobileVersion: function() {
            location.href = 'index_mobile.html';
        },
        showNormalVersion: function() {
            Ext.define('News', {
                extend: 'Ext.data.Model',
                fields: [
                    'id_news',
                    {
                        name: 'date_news',
                        type: 'date',
                        dateFormat: 'd/m/Y'
                    },
                    'titre_news',
                    'texte_news'
                ]
            });
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
            var newsPanel = Ext.create('Ext.grid.Panel', {
                flex: 1,
                autoScroll: true,
                title: 'Quelques news...',
                columns: [
                    {
                        xtype: 'datecolumn',
                        header: 'Date',
                        dataIndex: 'date_news',
                        format: 'd/m/Y',
                        flex: 3
                    },
                    {
                        header: 'Sujet',
                        dataIndex: 'titre_news',
                        flex: 10
                    },
                    {
                        xtype: 'actioncolumn',
                        items: [
                            {
                                icon: 'images/file.gif',
                                tooltip: 'Voir',
                                handler: function(grid, rowIndex, colIndex) {
                                    var rec = grid.getStore().getAt(rowIndex);
                                    Ext.Msg.alert(rec.get('titre_news'), rec.get('texte_news'));
                                }
                            }
                        ],
                        flex: 1
                    }
                ],
                store: Ext.create('Ext.data.Store', {
                    model: 'News',
                    sorters: [
                        {
                            property: 'date_news',
                            direction: 'DESC'
                        }
                    ],
                    filters: [
                        {
                            filterFn: function(item) {
                                return item.get('date_news') > Ext.Date.subtract(new Date(), Ext.Date.MONTH, 6);
                            }
                        }
                    ],
                    proxy: {
                        type: 'rest',
                        url: 'ajax/news.php',
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
                dockedItems: [
                    {
                        xtype: 'toolbar',
                        hidden: true,
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
                                    afficheFormulaireNews(false);
                                }
                            },
                            {
                                icon: 'images/modif.gif',
                                text: 'Modifier',
                                tooltip: 'Modifier',
                                handler: function(button) {
                                    afficheFormulaireNews(true);
                                }
                            },
                            {
                                icon: 'images/delete.gif',
                                tooltip: 'Supprimer',
                                text: 'Supprimer',
                                handler: function(button) {
                                    var rec = button.up('grid').getView().getSelectionModel().getSelection()[0];
                                    Ext.Msg.show({
                                        title: 'Effacer la news ?',
                                        msg: 'Vous allez effacer une news, confirmez vous cette action ?',
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
            });
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
            var sourceUrl = "https://api.flickr.com/services/rest?method=flickr.photos.search&sort=relevance&api_key=5dc398923c15e04b803a4022344d39c6&text=volleyball&format=json&extras=url_c%2Cicon_urls_deep";
            jsonFlickrApi = function(response) {
                var photos = response.photos.photo;
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
                        }
                        else {
                            console.log(photo);
                        }
                    },
                    interval: 3000
                };
                Ext.TaskManager.start(task);

            };
            Ext.data.JsonP.request({
                url: sourceUrl,
                callbackKey: 'jsonFlickrApi',
                success: jsonFlickrApi
            });
            var photosPanel = Ext.create('Ext.panel.Panel', {
                flex: 1,
                layout: 'fit',
                items: [
                    changingImage
                ]
            });
            var lastResultsPanel = Ext.create('Ext.grid.Panel', {
                title: 'Derniers résultats',
                flex: 1,
                autoScroll: true,
                columns: [
                    {
                        header: 'Compétition',
                        dataIndex: 'competition',
                        width: 180
                    },
                    {
                        header: 'Journée',
                        dataIndex: 'division_journee',
                        width: 150,
                        renderer: function(val, meta, record) {
                            var url = record.get('url');
                            return '<a href="' + url + '" target="blank">' + val + '</a>';
                        }
                    },
                    {
                        header: 'Domicile',
                        dataIndex: 'equipe_domicile',
                        width: 140,
                        renderer: function(val, meta, record) {
                            var displayValue = val;
                            switch (record.get('code_competition')) {
                                case 'm':
                                case 'f':
                                    displayValue = displayValue + ' (' + record.get('rang_dom') + ')';
                                    break;
                                default :
                                    break;
                            }
                            if (record.get('score_equipe_dom') > record.get('score_equipe_ext')) {
                                return '<span style="color:green;font-weight:bold">' + displayValue + '</span>';
                            }
                            return displayValue;
                        }
                    },
                    {
                        header: '',
                        dataIndex: 'score_equipe_dom',
                        width: 15
                    },
                    {
                        header: '',
                        dataIndex: 'score_equipe_ext',
                        width: 15
                    },
                    {
                        header: 'Extérieur',
                        dataIndex: 'equipe_exterieur',
                        width: 140,
                        renderer: function(val, meta, record) {
                            var displayValue = val;
                            switch (record.get('code_competition')) {
                                case 'm':
                                case 'f':
                                    displayValue = displayValue + ' (' + record.get('rang_ext') + ')';
                                    break;
                                default :
                                    break;
                            }
                            if (record.get('score_equipe_ext') > record.get('score_equipe_dom')) {
                                return '<span style="color:green;font-weight:bold">' + displayValue + '</span>';
                            }
                            return displayValue;
                        }
                    },
                    {
                        header: 'S1',
                        dataIndex: 'set1',
                        width: 45
                    },
                    {
                        header: 'S2',
                        dataIndex: 'set2',
                        width: 45
                    },
                    {
                        header: 'S3',
                        dataIndex: 'set3',
                        width: 45
                    },
                    {
                        header: 'S4',
                        dataIndex: 'set4',
                        width: 45
                    },
                    {
                        header: 'S5',
                        dataIndex: 'set5',
                        width: 45
                    },
                    {
                        header: 'Date',
                        xtype: 'datecolumn',
                        format: 'd/m/Y',
                        dataIndex: 'date_reception',
                        width: 80
                    }
                ],
                store: Ext.create('Ext.data.Store', {
                    fields: [
                        'competition',
                        'code_competition',
                        'division_journee',
                        'equipe_domicile',
                        'equipe_exterieur',
                        'score_equipe_dom',
                        'score_equipe_ext',
                        'rang_dom',
                        'rang_ext',
                        'set1',
                        'set2',
                        'set3',
                        'set4',
                        'set5',
                        'date_reception',
                        'url'
                    ],
                    proxy: {
                        type: 'ajax',
                        url: 'ajax/getLastResults.php',
                        reader: {
                            type: 'json',
                            root: 'results'
                        }
                    },
                    autoLoad: true
                })
            });
            var lastPostsPanel = Ext.create('Ext.grid.Panel', {
                text: 'Derniers posts du forum...',
                title: 'Derniers posts',
                flex: 1,
                autoScroll: true,
                columns: [
                    {
                        header: 'Titre',
                        flex: 7,
                        dataIndex: 'title',
                        renderer: function(value, meta, record) {
                            return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', record.get('guid'), value);
                        }
                    },
                    {
                        header: 'Auteur',
                        flex: 4,
                        dataIndex: 'creator'
                    },
                    {
                        header: 'Catégorie',
                        flex: 3,
                        dataIndex: 'category'
                    },
                    {
                        header: 'Date',
                        flex: 3,
                        dataIndex: 'pubdate',
                        xtype: 'datecolumn',
                        format: 'd/m/Y h:i'
                    }
                ],
                store: Ext.create('Ext.data.Store', {
                    fields: [
                        'title',
                        'creator',
                        'category',
                        {
                            name: 'pubdate',
                            type: 'date'
                        },
                        'description',
                        'guid'
                    ],
                    proxy: {
                        type: 'ajax',
                        url: 'ajax/getLastPosts.php',
                        reader: {
                            type: 'json',
                            root: 'results'
                        }
                    },
                    filters: [
                        function(item) {
                            return item.get('pubdate') > Date.parse('01/01/2013');
                        }
                    ],
                    autoLoad: true
                })
            });
            Ext.create('Ext.panel.Panel', {
                layout: 'border',
                width: 1000,
                height: 960,
                frame: true,
                renderTo: Ext.get('accueil'),
                items: [
                    {
                        region: 'north',
                        flex: 1,
                        layout: {
                            type: 'hbox',
                            align: 'stretch'
                        },
                        defaults: {
                            margin: 10
                        },
                        items: [
                            newsPanel,
                            photosPanel
                        ]
                    },
                    {
                        region: 'center',
                        flex: 2,
                        layout: {
                            type: 'vbox',
                            align: 'stretch'
                        },
                        defaults: {
                            margin: 10
                        },
                        items: [
                            lastResultsPanel,
                            lastPostsPanel
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
});