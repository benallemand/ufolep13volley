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
            Ext.Msg.show({
                title: 'Mobile?',
                msg: 'Accéder à la version mobile?',
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function (btn) {
                    if (btn === 'yes') {
                        window.location = 'index_mobile.php';
                    }
                }
            });
        }
    }
});