Ext.define('Ufolep13Volley.view.form.field.combo.hour', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_hour',
    fieldLabel: "Heure de réception",
    name: 'hour_court',
    displayField: 'name',
    valueField: 'name',
    store: {
        type: 'Hour',
    },
    queryMode: 'local',
    allowBlank: true,
    forceSelection: true
});