Ext.define('Ufolep13Volley.view.mobile.Main', {
    extend: 'Ext.NavigationView',
    requires: [],
    config: {
        items: [
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
