Ext.define('Ufolep13Volley.store.Commission', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Commission',
        proxy: {
            type: 'rest',
            url: 'ajax/commission.php',
            reader: {
                type: 'json',
                root: 'results'
            },
            writer: {
                type: 'json'
            },
            listeners: {
                exception: function(proxy, response, operation) {
                    var responseJson = Ext.decode(response.responseText);
                    Ext.MessageBox.show({
                        title: 'Erreur',
                        msg: responseJson.message,
                        icon: Ext.MessageBox.ERROR,
                        buttons: Ext.Msg.OK
                    });
                }
            }
        },
        autoLoad: true,
        autoSync: true}
}));