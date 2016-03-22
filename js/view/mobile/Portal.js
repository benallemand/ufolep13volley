Ext.define('Ufolep13Volley.view.mobile.Portal', {
    extend: 'Ext.NavigationView',
    requires: [
        'Ext.TitleBar'
    ],
    config: {
        items: [
            {
                title: 'Portail Equipe'
            },
            {
                xtype: 'toolbar',
                docked: 'bottom',
                items: [
                    {
                        text: '',
                        icon: 'images/mobile/phonebook.png',
                        action: 'getPhonebook'
                    },
                    {
                        text: '',
                        icon: 'images/mobile/cup.png',
                        action: 'getLastResults'
                    },
                    {
                        text: '',
                        icon: 'images/mobile/man.png',
                        action: 'getMyPlayers'
                    },
                    {
                        text: '',
                        icon: 'images/mobile/unlock.png',
                        action: 'disconnect'
                    }
                ]
            }
        ]
    }
});
