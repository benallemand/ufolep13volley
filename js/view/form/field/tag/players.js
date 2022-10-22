Ext.define('Ufolep13Volley.view.form.field.tag.players', {
    extend: 'Ext.form.field.Tag',
    alias: 'widget.tag_field_players',
    fieldLabel: 'Joueurs',
    name: 'player_ids[]',
    store: {
        type: 'Players',
        autoLoad: false,
        sorters: [
            {
                property: 'id_club',
                direction: 'ASC'
            },
            {
                property: 'sexe',
                direction: 'ASC'
            }
        ]
    },
    queryMode: 'local',
    displayField: 'full_name',
    tpl: '<tpl for=".">' +
        '<tpl if="est_actif">' +
        '<div class="x-boundlist-item"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
        '<tpl else>' +
        '<div class="x-boundlist-item" style="background: pink"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
        '</tpl>' +
        '</tpl>',
    valueField: 'id',
    forceSelection: true
});