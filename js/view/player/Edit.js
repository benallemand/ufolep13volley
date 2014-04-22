Ext.define('Ufolep13Volley.view.player.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.playeredit',
    title: 'Joueur',
    layout: 'fit',
    autoShow: true,
    buttons: [
        {
            text: 'Save',
            action: 'save'
        },
        {
            text: 'Cancel',
            scope: this,
            handler: this.close
        }
    ],
    items: [
        {
            xtype: 'form',
            defaults: {
                xtype: 'textfield',
                anchor: '100%'
            },
            layout: 'anchor',
            items: [
                {
                    xtype: 'hidden',
                    name: 'id',
                    fieldLabel: 'Id'
                },
                {
                    name: 'prenom',
                    fieldLabel: 'Pr�nom',
                    allowBlank: false
                },
                {
                    name: 'nom',
                    fieldLabel: 'Nom',
                    allowBlank: false
                },
                {
                    name: 'num_licence',
                    fieldLabel: 'Num�ro de licence',
                    minLength: 8,
                    maxLength: 8,
                    allowBlank: false
                },
                {
                    name: 'sexe',
                    fieldLabel: 'Sexe',
                    allowBlank: false
                },
                {
                    name: 'departement_affiliation',
                    fieldLabel: 'D�partement',
                    xtype: 'numberfield',
                    value: 13,
                    allowBlank: false
                },
                {
                    name: 'est_actif',
                    xtype: 'checkboxfield',
                    fieldLabel: 'Actif ?',
                    checked: true
                },
                {
                    name: 'id_club',
                    fieldLabel: 'Club'
                },
                {
                    name: 'est_licence_valide',
                    xtype: 'checkboxfield',
                    fieldLabel: 'Licence Valide ?',
                    checked: true
                },
                {
                    name: 'date_homologation',
                    xtype: 'datefield',
                    format: 'd/m/Y',
                    fieldLabel: "Date d'homologation"
                },
                {
                    name: 'est_responsable_club',
                    xtype: 'checkboxfield',
                    fieldLabel: 'Responsable ?'
                },
                {
                    xtype: 'fieldset',
                    title: 'D�tails du responsable',
                    hidden: true,
                    items: [
                        {
                            name: 'telephone',
                            fieldLabel: 'Num�ro de t�l�phone'
                        },
                        {
                            name: 'email',
                            fieldLabel: 'Email'
                        },
                        {
                            name: 'adresse',
                            fieldLabel: 'Adresse'
                        },
                        {
                            name: 'code_postal',
                            fieldLabel: 'Code postal'
                        },
                        {
                            name: 'ville',
                            fieldLabel: 'Ville'
                        },
                        {
                            name: 'telephone2',
                            fieldLabel: 'Num�ro de t�l�phone secondaire'
                        },
                        {
                            name: 'email2',
                            fieldLabel: 'Email secondaire'
                        }
                    ]
                }
            ]
        }
    ]
});