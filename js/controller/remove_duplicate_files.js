Ext.define('Ufolep13Volley.controller.remove_duplicate_files', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'panel[title=Indicateurs]': {
                    added: this.add_toolbar
                },
                'button[action=remove_duplicate_files]': {
                    click: this.remove_duplicate_files
                }
            }
        );
    },
    add_toolbar: function (panel) {
        panel.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'button',
                    action: 'remove_duplicate_files',
                    text: 'Nettoyer les fichiers dupliqués'
                }
            ]
        });
    },
    remove_duplicate_files: function (button) {
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
                    url: "/rest/action.php/files/cleanup_files",
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