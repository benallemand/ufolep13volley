Ext.define('Ufolep13Mobile.view.Main', {
    extend: 'Ext.NavigationView',
    requires: [
        'Ext.TitleBar',
        'Ufolep13Mobile.view.Tournaments'
    ],
    config: {
        items: [
            {
                title: 'Competitions UFOLEP',
                layout: 'vbox',
                items: [
                    {
                        xtype: 'listtournaments'
                    }
                ]
            }
        ]
    }
});
