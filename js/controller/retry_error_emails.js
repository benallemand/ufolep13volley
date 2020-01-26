Ext.define('Ufolep13Volley.controller.retry_error_emails', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'panel[title=Indicateurs] > toolbar[dock=top]': {
                    added: this.add_button
                },
                'button[action=retry_error_emails]': {
                    click: this.retry_error_emails
                }
            }
        );
    },
    add_button: function (toolbar) {
        toolbar.add({
            xtype: 'button',
            action: 'retry_error_emails',
            text: 'Retenter les mails en erreur'
        });
    },
    retry_error_emails: function (button) {
        Ext.Msg.show({
            title: button.text,
            msg: 'Etes-vous certain de vouloir effectuer cette action ?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                Ext.Ajax.request({
                    url: "ajax/retry_error_emails.php",
                    method: 'POST',
                    success: function () {
                        Ext.Msg.alert('Succès', "L'opération a été réalisée avec succès.");
                    },
                    failure: function (response) {
                        if (response.status === '404') {
                            Ext.Msg.alert('Erreur', "La page n'a pas été trouvée !");
                            return;
                        }
                        var response_json = Ext.decode(response.responseText);
                        Ext.create('Ext.window.Window', {
                            title: 'Erreur (copiable)',
                            height: 500,
                            width: 700,
                            maximizable: true,
                            layout: 'fit',
                            items: {
                                xtype: 'textarea',
                                value: response_json.message
                            }
                        }).show();
                    }
                });
            }
        });
    }
});