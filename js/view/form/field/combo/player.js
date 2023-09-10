Ext.define('Ufolep13Volley.view.form.field.combo.player', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_player',
    multiselect: false,
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
    queryMode: 'remote',
    displayField: 'full_name',
    tpl: '<tpl for=".">' +
        '<tpl if="est_actif">' +
        '<div class="x-boundlist-item"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
        '<tpl else>' +
        '<div class="x-boundlist-item" style="background: pink"><img src="{path_photo}" width="50px" style="vertical-align: middle"/><span>{full_name}</span></div>' +
        '</tpl>' +
        '</tpl>',
    valueField: 'id',
    forceSelection: true,
    triggerAction: 'last',
});