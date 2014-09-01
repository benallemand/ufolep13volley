Ext.define('Ufolep13Volley.store.MyTeam', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Team',
        proxy: {
            type: 'ajax',
            url: 'ajax/getMonEquipe.php',
            reader: {
                type: 'json',
                root: 'results'
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
        autoLoad: false}
}));