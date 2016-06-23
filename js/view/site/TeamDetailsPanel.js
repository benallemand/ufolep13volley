Ext.define('Ufolep13Volley.view.site.TeamDetailsPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.teamDetailsPanel',
    layout: 'border',
    defaults: {
        border: false
    },
    items: [
        {
            region: 'north',
            xtype: 'headerPanel'
        },
        {
            region: 'center',
            layout: 'border',
            items: [
                {
                    region: 'center',
                    flex: 2,
                    xtype: 'formTeamDetails'
                },
                {
                    region: 'east',
                    flex: 1,
                    title: 'Photo',
                    layout: 'fit',
                    items: {
                        xtype: 'image',
                        id: 'teamPicture',
                        src: ''
                    }
                }
            ]
        }
    ]
});
