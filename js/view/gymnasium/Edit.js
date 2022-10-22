Ext.define('Ufolep13Volley.view.gymnasium.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.gymnasiumedit',
    title: 'Gymnases',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        defaults: {
            xtype: 'textfield',
            anchor: '90%'
        },
        url: '/rest/action.php/court/saveGymnasium',
        autoScroll: true,
        layout: 'anchor',
        items: [
            {
                xtype: 'hidden',
                name: 'id',
                fieldLabel: 'Id',
                msgTarget: 'under'
            },
            {
                name: 'nom',
                fieldLabel: 'Nom',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'adresse',
                fieldLabel: 'Adresse',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'code_postal',
                fieldLabel: 'Code Postal',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'ville',
                fieldLabel: 'Ville',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'gps',
                fieldLabel: 'GPS',
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                xtype: 'numberfield',
                name: 'nb_terrain',
                fieldLabel: 'Nombre de terrains',
                allowBlank: false,
                minValue: 1,
                maxValue: 6,
                msgTarget: 'under'
            }
        ],
        buttons: [
            {
                text: 'Sauver',
                action: 'save',
                formBind: true,
                disabled: true
            },
            {
                text: 'Annuler',
                action: 'cancel',
            }
        ]
    }
});