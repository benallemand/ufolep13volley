Ext.define('Ufolep13Volley.model.Club', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        'nom',
        'affiliation_number',
        'nom_responsable',
        'prenom_responsable',
        'tel1_responsable',
        'tel2_responsable',
        'email_responsable'
    ]
});
