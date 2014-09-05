Ext.define('Ufolep13Volley.view.site.MainPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.mainPanel',
    layout: 'border',
    defaults: {
        border: false
    },
    items: [
        Ext.is.Phone ? null : {
            region: 'north',
            height: Ext.is.Phone ? 0 : 280,
            split: true,
            layout: 'border',
            defaults: {
                border: false
            },
            items: [
                {
                    region: 'center',
                    flex: 2,
                    layout: 'center',
                    defaults: {
                        border: false
                    },
                    items: {
                        layout: {
                            type: 'vbox',
                            align: 'center'
                        },
                        xtype: 'panel',
                        items: [
                            {
                                width: 500,
                                height: 50,
                                xtype: 'banner'
                            },
                            {
                                width: 400,
                                height: 50,
                                xtype: 'image',
                                src: './images/JeuAvantEnjeu.jpg'
                            }
                        ]
                    }
                },
                {
                    region: 'east',
                    width: 400,
                    layout: 'fit',
                    defaults: {
                        border: false
                    },
                    items: {
                        xtype: 'image',
                        id: 'randomImage',
                        src: '',
                        style: {
                            cursor: 'pointer'
                        },
                        listeners: {
                            el: {
                                click: function() {
                                    window.open('.', '_self', false);
                                }
                            },
                            render: function(changingImage) {
                                Ext.Ajax.request({
                                    url: 'ajax/getVolleyballImages.php',
                                    success: function(response) {
                                        var responseJson = Ext.decode(response.responseText);
                                        var photos = responseJson.photos.photo;
                                        var photo = photos[Ext.Number.randomInt(0, photos.length - 1)];
                                        var src = Ext.String.format("https://farm{0}.staticflickr.com/{1}/{2}_{3}.jpg", photo.farm, photo.server, photo.id, photo.secret);
                                        changingImage.setSrc(src);
                                        var task = {
                                            run: function() {
                                                var photo = photos[Ext.Number.randomInt(0, photos.length - 1)];
                                                var src = Ext.String.format("https://farm{0}.staticflickr.com/{1}/{2}_{3}.jpg", photo.farm, photo.server, photo.id, photo.secret);
                                                changingImage.setSrc(src);
                                            },
                                            interval: 3000
                                        };
                                        Ext.TaskManager.start(task);
                                    }
                                });
                            }
                        }
                    }
                }
            ]
        },
        {
            region: 'north',
            xtype: 'headerPanel'
        },
        {
            region: 'center',
            xtype: 'tabpanel',
            defaults: {
                autoScroll: true
            },
            items: [
                {
                    xtype: 'LastResultsGrid'
                },
                {
                    xtype: 'LastPostsGrid'
                },
                {
                    xtype: 'WebSitesGrid'
                }
            ]
        }
    ]
});
