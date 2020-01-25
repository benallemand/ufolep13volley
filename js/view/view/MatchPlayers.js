var match_player_tpl = new Ext.XTemplate(
    '<tpl for=".">',
    '<div style="margin-bottom: 10px;" class="thumb-wrap">',
    '<img src="{path_photo}" />',
    '<br/><span>{full_name}</span>',
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