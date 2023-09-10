Ext.define('Ufolep13Volley.store.Ufolep', {
    extend: 'Ext.data.Store',
    listeners: {
        load: function (store, records, successful, operation) {
            if (!successful) {
                Ext.Msg.alert("Erreur", Ext.decode(operation.error.response.responseText).message);
            }
        }
    }
});