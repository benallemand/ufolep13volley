var match_player_tpl = new Ext.XTemplate(
    '<tpl for=".">',
    '<div style="margin: 10px;float: left;width: 120px; height: 170px" class="thumb-wrap">',
    '<p style="text-align: center"><img src="{path_photo}" width="100px" height="129px"/></p>',
    '<tpl if="est_actif && date_homologation && date_homologation <= date_reception">',
    '<p style="text-align: center">{full_name}</p>',
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
    emptyText: 'Aucun joueur associé'
});