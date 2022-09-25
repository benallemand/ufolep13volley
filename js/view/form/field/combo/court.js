Ext.define('Ufolep13Volley.view.form.field.combo.court', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_court',
    fieldLabel: 'Gymnase',
    name: 'id_court',
    displayField: 'full_name',
    valueField: 'id',
    store: {
        type: 'Gymnasiums',
    },
    queryMode: 'local',
    allowBlank: true,
    forceSelection: true
});