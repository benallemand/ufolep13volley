Ext.define('Ufolep13Volley.controller.Menu', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [
        {
            ref: 'LastCommitField',
            selector: 'tbtext[id=textShowLastCommit]'
        }
    ],
    init: function () {
        this.control(
            {
                'mainPanel': {
                    added: this.proposeMobileVersion
                },
                'tbtext[id=textShowLastCommit]': {
                    added: this.showLastCommitInformations
                }
            });
    },
    showLastCommitInformations: function () {
        var me = this;
        Ext.Ajax.request({
            url: 'ajax/getLastCommit.php',
            success: function (response) {
                var text = response.responseText;
                me.getLastCommitField().setText(text);
            }
        });
    },
    proposeMobileVersion: function () {
        if (Ext.is.Phone) {
            window.location = 'new_site/';
        }
    }
});