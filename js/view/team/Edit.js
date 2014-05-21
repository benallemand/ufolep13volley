Ext.define('Ufolep13Volley.view.team.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.teamedit',
    title: "Modification de l'équipe",
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/modifierMonEquipe.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id_equipe',
                name: 'id_equipe'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Club',
                name: 'id_club',
                displayField: 'nom',
                valueField: 'id',
                store: 'Clubs',
                queryMode: 'local'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Responsable',
                name: 'responsable'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Téléphone 1',
                name: 'telephone_1'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Téléphone 2',
                name: 'telephone_2'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Email',
                name: 'email'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Réception le',
                name: 'jour_reception'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Horaire',
                name: 'heure_reception'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Gymnase',
                name: 'gymnase'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Localisation GPS',
                name: 'localisation',
                regex: /^\d+[\.]\d+,\d+[\.]\d+$/,
                regexText: "Merci d'utiliser le format Google Maps, par exemple : 43.410496,5.242646"
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Site web',
                name: 'site_web'
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function() {
                    this.up('window').close();
                }
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                action: 'save'
            }
        ]
    }
});