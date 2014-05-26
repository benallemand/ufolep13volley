Ext.define('Ufolep13.controller.Main', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ufolep13.view.Tournaments',
        'Ufolep13.view.Matches'
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
