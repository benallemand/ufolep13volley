import { createRulesComponent } from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement championnat mixte',
    subtitle: '4 x 4 (mixité obligatoire)',
    lastUpdate: '23 octobre 2016',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'La CTSD UFOLEP Volley des Bouches du Rhône organise un championnat mixte 4 x 4 attribuant un titre départemental annuel.',
                'La mixité impose en permanence au moins un joueur et une joueuse sur le terrain (ou trois joueurs lors d’un match à effectif réduit).',
                'Le terme « joueur » désigne indistinctement joueuses et joueurs dans tout le document.',
            ],
        },
        {
            num: 2,
            title: "Inscription d'une équipe",
            content: [
                'Inscription possible à partir de quatre licenciés UFOLEP ; un nouveau joueur peut être ajouté à tout moment après information du responsable des classements.',
                'Les nouvelles équipes démarrent dans la division inférieure ; la CTSD peut organiser un tournoi de sélection pour répartir les places disponibles en divisions supérieures.',
                'Si au moins trois licenciés d’une équipe non réinscrite créent une nouvelle formation, celle-ci peut conserver la place sportive de l’équipe d’origine.',
            ],
        },
        {
            num: 3,
            title: 'Règles de jeu',
            content: [
                'Les règles de jeu, l’arbitrage et la feuille de match se réfèrent au règlement FFVB et au règlement général UFOLEP Volley 13.',
            ],
        },
        {
            num: 4,
            title: 'Organisation du championnat',
            content: [
                'Le championnat est découpé en divisions (1, 2, 3, …) jouées en deux demi-saisons.',
                'À l’issue de chaque demi-saison, les deux derniers (sauf dans la dernière division) descendent et les deux premiers des divisions inférieures montent. Toute exception est annoncée par courrier.',
                'La CTSD peut maintenir ou promouvoir certaines équipes pour équilibrer les divisions.',
                'Le titre départemental est décerné lors de la deuxième demi-saison.',
            ],
        },
        {
            num: 5,
            title: 'Rotation',
            content: [
                'Tous les joueurs se succèdent au service, mais la rotation n’est obligatoire qu’au moment de servir.',
                'Les postes fixes sont autorisés à condition de conserver l’ordre initial de service.',
            ],
        },
        {
            num: 6,
            title: 'Zones et titre départemental',
            content: [
                'Aucune distinction entre zone avant et zone arrière : tous les joueurs peuvent attaquer depuis n’importe quelle zone.',
                'Le titre départemental est attribué à l’issue de la deuxième demi-saison.',
            ],
        },
        {
            num: 7,
            title: 'Libéro',
            content: ['Le libéro n’est pas autorisé dans ce championnat.'],
        },
        {
            num: 8,
            title: 'Remplacements',
            content: [
                'Chaque joueur peut être remplacé au cours d’un set puis reprendre sa place, dans la limite de quatre remplacements.',
                'L’ordre initial de service doit toujours être respecté.',
            ],
        },
        {
            num: 9,
            title: 'Filet',
            content: ['Hauteur de filet : 2,43 m pour les divisions supérieures et 2,35 m pour les divisions inférieures.'],
        },
        {
            num: 10,
            title: 'Prêt de joueur/se',
            content: ['Le prêt est autorisé conformément au règlement général UFOLEP Volley 13.'],
        },
        {
            num: 11,
            title: 'Pénétrations et filet',
            content: ['Les fautes de pénétration ou de contact filet sont jugées selon les règles FFVB (mise à jour janvier 2015).'],
        },
        {
            num: 12,
            title: 'Inscription au championnat',
            content: [
                'Confirmation auprès de la CTSD avant le 30 septembre pour la première demi-saison et avant le 30 janvier pour la seconde.',
            ],
        },
    ],
});
