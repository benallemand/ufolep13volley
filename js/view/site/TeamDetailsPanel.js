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
            split: true,
            xtype: 'headerPanel'
        },
        {
            region: 'center',
            layout: 'hbox',
            autoScroll: true,
            defaults: {
                border: false
            },
            items: [
                {
                    region: 'center',
                    flex: 1,
                    height: 800,
                    xtype: 'formTeamDetails'
                },
                {
                    region: 'east',
                    width: 400,
                    xtype: 'image',
                    id: 'teamPicture',
                    src: ''
                }
            ]
        }
    ]
});
