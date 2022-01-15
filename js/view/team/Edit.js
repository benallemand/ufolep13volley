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
        url: 'ajax/saveTeam.php',
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
                queryMode: 'local',
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Competition',
                name: 'code_competition',
                displayField: 'libelle',
                valueField: 'code_competition',
                store: 'Competitions',
                queryMode: 'local'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Nom',
                name: 'nom_equipe',
                allowBlank: false
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel',
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