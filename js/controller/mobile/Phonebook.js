Ext.define('Ufolep13Volley.controller.mobile.Phonebook', {
    extend: 'Ext.app.Controller',
    requires: ['Ufolep13Volley.view.mobile.Tournaments'],
    config: {
        refs: {
            mainPanel: 'navigationview',
            buttonPhonebook: 'button[action=getPhonebook]'
        },
        control: {
            buttonPhonebook: {
                tap: 'showPhonebook'
            }
        }
    },
    showPhonebook: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listtournaments'
        });
        Ext.getStore('Phonebooks').load();
    }
}
);
