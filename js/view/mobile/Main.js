Ext.define('Ufolep13Volley.view.mobile.Main', {
    extend: 'Ext.NavigationView',
    requires: [],
    config: {
        items: [
            {
                title: 'UFOLEP 13 VOLLEY',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                xtype: 'formpanel',
                url: 'ajax/login.php',
                defaults: {
                    margin: 20
                },
                items: [
                    {
                        xtype: 'textfield',
                        labelAlign: 'top',
                        name: 'login',
                        label: 'Login'
                    },
                    {
                        xtype: 'passwordfield',
                        labelAlign: 'top',
                        name: 'password',
                        label: 'Mot de passe'
                    },
                    {
                        xtype: 'button',
                        text: 'Connexion',
                        action: 'login'
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
