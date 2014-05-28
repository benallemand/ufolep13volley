Ext.define('Ufolep13Volley.view.mobile.Main', {
    extend: 'Ext.NavigationView',
    requires: [
        'Ext.TitleBar'
    ],
    config: {
        items: [
            {
                title: 'Competitions UFOLEP'
            },
            {
                xtype: 'toolbar',
                docked: 'bottom',
                items: [
                    {
                        text: 'Annuaire',
                        icon: 'images/phonebook.png'
                    },
                    {
                        text: 'Matches',
                        icon: 'images/cup.png'
                    }
                ]
            }

        ]
    }
});
