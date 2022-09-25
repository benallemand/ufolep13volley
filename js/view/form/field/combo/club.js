Ext.define('Ufolep13Volley.view.form.field.combo.club', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_club',
    queryMode: 'local',
    store: 'Clubs',
    displayField: 'nom',
    valueField: 'id',
    msgTarget: 'under',
    anchor: '100%',
    forceSelection: true
});