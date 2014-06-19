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
                        icon: 'images/phonebook.png',
                        action: 'getPhonebook'
                    },
                    {
                        text: '',
                        icon: 'images/cup.png',
                        action: 'getLastResults'
                    },
                    {
                        text: '',
                        icon: 'images/man.png',
                        action: 'getMyPlayers'
                    },
                    {
                        text: '',
                        icon: 'images/exit.png',
                        action: 'disconnect'
                    }
                ]
            }
        ]
    }
});
