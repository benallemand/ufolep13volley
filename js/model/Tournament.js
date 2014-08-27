Ext.define('Ufolep13Volley.model.Tournament', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
        fields: [
            {
                name: 'id',
                type: 'int'
            },
            'code_competition',
            'libelle'
        ]
}));
