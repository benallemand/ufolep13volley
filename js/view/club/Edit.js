Ext.define('Ufolep13Volley.view.club.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.clubedit',
    title: 'Clubs',
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
        url: '/rest/action.php/club/saveClub',
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
                name: 'affiliation_number',
                fieldLabel: "Numéro d'affiliation",
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'nom_responsable',
                fieldLabel: "Nom du responsable",
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'prenom_responsable',
                fieldLabel: "Prénom du responsable",
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'tel1_responsable',
                fieldLabel: "Téléphone du responsable",
                allowBlank: false,
                msgTarget: 'under'
            },
            {
                name: 'tel2_responsable',
                fieldLabel: "Autre téléphone du responsable",
                allowBlank: true,
                msgTarget: 'under'
            },
            {
                name: 'email_responsable',
                fieldLabel: "Adresse email du responsable",
                allowBlank: false,
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