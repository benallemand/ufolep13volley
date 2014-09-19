Ext.application({
    requires: [],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'match.LastResultsGrid', 'forum.LastPostsGrid', 'team.WebSitesGrid', 'images.Coverflow'],
    controllers: ['Menu'],
    stores: ['LastResults', 'LastPosts', 'WebSites', 'Images'],
    models: ['LastResult', 'LastPost', 'WebSite', 'Image'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'mainPanel'
            }
        });
    }
});