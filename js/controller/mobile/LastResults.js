Ext.define('Ufolep13Volley.controller.mobile.LastResults', {
    extend: 'Ext.app.Controller',
    requires: ['Ufolep13Volley.view.mobile.LastResults'],
    config: {
        refs: {
            mainPanel: 'navigationview',
            buttonResultats: 'button[action=getLastResults]'
        },
        control: {
            buttonResultats: {
                tap: 'showLastResults'
            }
        }
    },
    showLastResults: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listlastresults'
        });
    }
}
);
