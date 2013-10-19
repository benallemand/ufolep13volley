Ext.onReady(function() {
    Ext.define('MembreCommission', {
        extend: 'Ext.data.Model',
        fields: [
            'id_commission',
            'nom',
            'prenom',
            'fonction',
            'telephone1',
            'telephone2',
            'email',
            'photo',
            'type'
        ]
    });
    Ext.create('Ext.view.View', {
        renderTo: Ext.get('commission'),
        tpl: new Ext.XTemplate(
                '<h1>Membres CTSD</h1>',
                '<tpl for=".">',
                '<tpl if="type == \'membre\'">',
                '<div class="membre">',
                '<div class="photo"><img src="{photo}" width="100px" height="100px"></div>',
                '<div class="details">',
                '<h1>{prenom} {nom}</h1>',
                '<ul>',
                '<li class="fonction">{fonction}</li>',
                '<li><span>{telephone1}</span></li>',
                '<li><span>{telephone2}</span></li>',
                '<li><span><a href="mailto:{email}" target="_blank">{email}</a></span></li>',
                '</ul>',
                '</div>',
                '</div>',
                '</tpl>',
                '</tpl>',
                '<h1>Supports</h1>',
                '<tpl for=".">',
                '<tpl if="type == \'support\'">',
                '<div class="membre">',
                '<div class="photo"><img src="{photo}" width="100px" height="100px"></div>',
                '<div class="details">',
                '<h1>{prenom} {nom}</h1>',
                '<ul>',
                '<li class="fonction">{fonction}</li>',
                '<li><span>{telephone1}</span></li>',
                '<li><span>{telephone2}</span></li>',
                '<li><span><a href="mailto:{email}" target="_blank">{email}</a></span></li>',
                '</ul>',
                '</div>',
                '</div>',
                '</tpl>',
                '</tpl>'
                ),
        itemSelector: 'div.membre',
        store: Ext.create('Ext.data.Store', {
            model: 'MembreCommission',
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
            autoSync: true
        })
    });
});