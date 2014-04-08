Ext.define('Ufolep13Mobile.view.Main', {
    extend: 'Ext.NavigationView',
    requires: [
        'Ext.TitleBar',
        'Ufolep13Mobile.view.SearchTournament',
        'Ufolep13Mobile.view.Tournaments'
    ],
    config: {
        items: [
            {
                title: 'Compétitions UFOLEP',
                layout: 'vbox',
                items: [
                    {
                        xtype: 'searchfieldtournament'
                    },
                    {
                        xtype: 'listtournaments'
                    }
                ]
            }
        ]
    }
});