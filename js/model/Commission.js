Ext.define('Ufolep13Volley.model.Commission', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        'id_commission',
        'nom',
        'prenom',
        'fonction',
        'telephone1',
        'telephone2',
        'email',
        'photo',
        'type',
        {
            name: 'nom_prenom_display',
            convert: function (val, record) {
                return "<img src=\'ajax/getImageFromText.php?text=\"" + btoa(record.data.prenom + ' ' + record.data.nom) + "\"\'/>";
            }
        },
        {
            name: 'telephone1_display',
            convert: function (val, record) {
                return "<img src=\'ajax/getImageFromText.php?text=\"" + btoa(record.data.telephone1) + "\"\'/>";
            }
        },
        {
            name: 'telephone2_display',
            convert: function (val, record) {
                return "<img src=\'ajax/getImageFromText.php?text=\"" + btoa(record.data.telephone2) + "\"\'/>";
            }
        },
        {
            name: 'email_display',
            convert: function (val, record) {
                return "<img src=\'ajax/getImageFromText.php?text=\"" + btoa(record.data.email) + "\"\'/>";
            }
        }
    ]
}));
