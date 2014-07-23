Ext.application({
    requires: [],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'new.Grid', 'match.LastResultsGrid', 'forum.LastPostsGrid', 'team.WebSitesGrid'],
    controllers: [],
    stores: ['News', 'LastResults', 'LastPosts', 'WebSites'],
    models: ['New', 'LastResult', 'LastPost', 'WebSite'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'mainPanel'
            }
        });
        var changingImage = Ext.getCmp('randomImage');
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
                        changingImage.setWidth(400);
                    },
                    interval: 3000
                };
                Ext.TaskManager.start(task);
            }
        });
    }
});