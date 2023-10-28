Ext.define('Ufolep13Volley.store.survey', {
    extend: 'Ext.data.Store',
    alias: 'store.survey',
    config: {
        model: 'Ufolep13Volley.model.survey',
        proxy: {
            type: 'rest',
            url: 'rest/action.php/matchmgr/get_survey'
        }
    }
});