Ext.define('Ufolep13Volley.view.mobile.Main', {
    extend: 'Ext.NavigationView',
    requires: [],
    config: {
        items: [
            {
                title: 'UFOLEP 13 VOLLEY',
                layout: {
                    type: 'vbox',
                    align: 'center',
                    pack: 'center'
                },
                xtype: 'formpanel',
                url: 'ajax/login.php',
                defaults: {
                    width: '90%',
                    margin : 5,
                    labelWidth : '50%'
                },
                items: [
                    {
                        xtype: 'textfield',
                        name: 'login',
                        label: 'Login'
                    },
                    {
                        xtype: 'passwordfield',
                        name: 'password',
                        label: 'Mot de passe'
                    },
                    {
                        xtype: 'button',
                        text: 'Connexion',
                        action: 'login',
                    width: '50%',
                    margin : 10
                    }
                ]
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
                    }
                ]
            }
        ]
    }
});
