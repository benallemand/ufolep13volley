Ext.define('Ufolep13Volley.view.form.MatchPlayers', {
    extend: 'Ext.form.Panel',
    alias: 'widget.form_match_players',
    title: "Cocher les joueurs du match",
    layout: 'form',
    url: 'rest/action.php/matchmgr/manage_match_players',
    items: [
        {
            xtype: 'hidden',
            fieldLabel: 'id_match',
            name: 'id_match',
        },
        {
            xtype: 'tag_field_players',
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
});