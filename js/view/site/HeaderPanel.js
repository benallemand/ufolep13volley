Ext.define('Ufolep13Volley.view.site.HeaderPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.headerPanel',
    layout: 'border',
    height: Ext.is.Phone ? 50 : 500,
    dockedItems: [
        {
            xtype: 'mainMenu',
            dock: 'bottom'
        }
    ],
    defaults: {
        border: false
    },
    items: Ext.is.Phone ? null : [
        {
            region: 'north',
            layout: 'center',
            defaults: {
                border: false
            },
            items: {
                width: 796,
                defaults: {
                    border: false
                },
                items: {
                    xtype: 'banner'
                }
            }
        },
        {
            region: 'center',
            layout: 'center',
            defaults: {
                border: false
            },
            items: {
                width: 300,
                defaults: {
                    border: false
                },
                items: {
                    xtype: 'image',
                    region: 'center',
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
                                            changingImage.setWidth(300);
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
        }
    ]
});
