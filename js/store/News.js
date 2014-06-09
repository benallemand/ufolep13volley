Ext.define('Ufolep13Volley.store.News', {
    extend: 'Ext.data.Store',
    model: 'Ufolep13Volley.model.New',
    sorters: [
        {
            property: 'date_news',
            direction: 'DESC'
        }
    ],
    filters: [
        {
            filterFn: function(item) {
                return item.get('date_news') > Ext.Date.subtract(new Date(), Ext.Date.MONTH, 6);
            }
        }
    ],
    proxy: {
        type: 'rest',
        url: 'ajax/news.php',
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
    autoSync: true
});