var match_player_tpl = new Ext.XTemplate(
    '<table>',
    '<tpl for=".">',
    '<div style="margin: 10px;float: left;width: 80px; height: 180px" class="thumb-wrap">',
    '<p style="text-align: center;"><button type="button" class="ctl-delete">Enlever</button></p>',
    '<p style="text-align: center"><img src="{path_photo}" width="50px" height="50px"/></p>',
    '<tpl if="is_valid_for_match">',
    '<p style="font-size:xx-small;text-align: center">{full_name}</p>',
    '<tpl else>',
    '<p style="text-align: center; color: pink">{full_name}</p>',
    '</tpl>',
    '</div>',
    '</tpl>'
);
Ext.define('Ufolep13Volley.view.view.MatchPlayers', {
    extend: 'Ext.view.View',
    alias: 'widget.view_match_players',
    title: "Actuels joueurs du match",
    store: 'MatchPlayers',
    tpl: match_player_tpl,
    itemSelector: 'div.thumb-wrap',
    emptyText: 'Aucun joueur associé',
    listeners: {
        itemclick: function (view, record, item, index, e, eOpts) {
            var target = e.target;
            if (target.tagName === 'BUTTON' && target.className === "ctl-delete") {
                Ext.Ajax.request({
                    url: "/rest/action.php/matchmgr/delete_match_player",
                    params: {
                        id_match: record.get('id_match'),
                        id_player: record.get('id'),
                    },
                    method: 'POST',
                    success: function () {
                        view.getStore().load({
                            params: {
                                id_match: record.get('id_match')
                            }
                        });
                    },
                    failure: function (response) {
                        if (response.status === '404') {
                            Ext.Msg.alert('Erreur', "La page n'a pas été trouvée !");
                            return;
                        }
                        var response_json = Ext.decode(response.responseText);
                        Ext.create('Ext.window.Window', {
                            title: 'Erreur (copiable)',
                            height: 500,
                            width: 700,
                            maximizable: true,
                            layout: 'fit',
                            items: {
                                xtype: 'textarea',
                                value: response_json.message
                            }
                        }).show();
                    }
                });
            }
        },
    }
});