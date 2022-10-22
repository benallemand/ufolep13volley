Ext.define('Ufolep13Volley.view.form.field.combo.team', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_team',
    displayField: 'team_full_name',
    valueField: 'id_equipe',
    store: {type: 'Teams'},
    queryMode: 'local',
    allowBlank: true,
    forceSelection: true
});