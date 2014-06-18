Ext.define('Ufolep13Volley.view.mobile.Portal', {
    extend: 'Ext.NavigationView',
    requires: [
        'Ext.TitleBar'
    ],
    config: {
        items: [
            {
                title: 'Mon Equipe'
            },
            {
                xtype: 'toolbar',
                docked: 'bottom',
                items: [
                    {
                        text: 'Annuaire',
                        icon: 'images/phonebook.png',
                        action: 'getPhonebook'
                    },
                    {
                        text: 'Résultats',
                        icon: 'images/cup.png',
                        action: 'getLastResults'
                    },
                    {
                        text: 'Mes Joueurs',
                        icon: 'images/man.png',
                        action: 'getMyPlayers'
                    },
                    {
                        text: 'Deconnexion',
                        icon: 'images/exit.png',
                        action: 'disconnect'
                    }
                ]
            }
        ]
    }
});
