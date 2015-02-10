Ext.define('Ufolep13Volley.model.Phonebook', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        'code_competition',
        'libelle_competition',
        {
            name: 'division',
            type: 'int'
        },
        'id_equipe',
        'nom_equipe'
    ]
}));
