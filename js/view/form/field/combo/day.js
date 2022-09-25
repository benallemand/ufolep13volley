Ext.define('Ufolep13Volley.view.form.field.combo.day', {
    extend: 'Ext.form.field.ComboBox',
    alias: 'widget.combo_day',
    fieldLabel: "Jour de réception",
    name: 'day_court',
    displayField: 'name',
    valueField: 'name',
    store: Ext.create('Ext.data.Store', {
        fields: ['name'],
        data: [
            {
                "name": "Lundi"
            },
            {
                "name": "Mardi"
            },
            {
                "name": "Mercredi"
            },
            {
                "name": "Jeudi"
            },
            {
                "name": "Vendredi"
            }
        ]
    }),
    queryMode: 'local',
    allowBlank: true,
    forceSelection: true
});