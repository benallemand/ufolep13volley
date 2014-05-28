Ext.define('Ufolep13Volley.controller.mobile.Main', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ufolep13Volley.view.mobile.Tournaments',
        'Ufolep13Volley.view.mobile.Matches'
    ],
    config: {
        refs: {
            mainPanel: 'navigationview',
            buttonPhonebook: 'button[text=Annuaire]',
            buttonMatches: 'button[text=Matches]'
        },
        control: {
            buttonPhonebook: {
                tap: 'showPhonebook'
            },
            buttonMatches: {
                tap: 'showMatches'
            }
        }
    },
    showPhonebook: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listtournaments'
        });
    },
    showMatches: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listmatches'
        });
    }
}
);
