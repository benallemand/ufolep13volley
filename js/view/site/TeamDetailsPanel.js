Ext.define('Ufolep13Volley.view.site.TeamDetailsPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.teamDetailsPanel',
    layout: {
        type: 'vbox',
        align: 'center'
    },
    autoScroll: true,
    bodyStyle: 'background-color: #C9D7E5;',
    items: {
        layout: 'border',
        width: 1280,
        height: 1200,
        items: [
            {
                region: 'north',
                xtype: 'headerPanel'
            },
            {
                region: 'north',
                xtype: 'titlePanel'
            },
            {
                region: 'center',
                layout: 'hbox',
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
    }
});
