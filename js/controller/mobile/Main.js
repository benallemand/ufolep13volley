Ext.define('Ufolep13Volley.controller.mobile.Main', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ufolep13Volley.view.mobile.Tournaments',
        'Ufolep13Volley.view.mobile.LastResults'
    ],
    config: {
        refs: {
            mainPanel: 'navigationview',
            buttonPhonebook: 'button[action=getPhonebook]',
            buttonResultats: 'button[action=getLastResults]'
        },
        control: {
            buttonPhonebook: {
                tap: 'showPhonebook'
            },
            buttonResultats: {
                tap: 'showLastResults'
            }
        }
    },
    showPhonebook: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listtournaments'
        });
    },
    showLastResults: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listlastresults'
        });
    }
}
);
