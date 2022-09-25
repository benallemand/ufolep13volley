Ext.define('Ufolep13Volley.view.form.field.combo.competition', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_competition',
    fieldLabel: 'Competition',
    displayField: 'libelle',
    valueField: 'id',
    store: 'Competitions',
    queryMode: 'local',
    msgTarget: 'under',
    forceSelection: true
});