import {createRulesComponent} from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement championnat féminin',
    subtitle: '4 x 4',
    lastUpdate: '14 juin 2013',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'La CTSD UFOLEP Volley des Bouches du Rhône organise un championnat féminin 4 x 4 attribuant un titre départemental annuel.',
                'La commission peut maintenir ou promouvoir une équipe pour équilibrer les divisions.',
            ],
        },
        {
            num: 2,
            title: "Inscription d'une équipe",
            content: [
                'Inscription possible à partir de quatre licenciées UFOLEP, avec ajout d’une nouvelle joueuse autorisé à tout moment après validation par le responsable des classements.',
                'Les nouvelles équipes démarrent dans la division inférieure ; la CTSD peut organiser un tournoi pour répartir les places disponibles dans les divisions supérieures.',
                'Si au moins trois joueuses d’une équipe non réinscrite créent une nouvelle équipe, celle-ci peut conserver la place sportive de l’équipe d’origine.',
            ],
        },
        {
            num: 3,
            title: 'Règles de jeu',
            content: [
                'Les rencontres suivent le règlement FFVB et le règlement général UFOLEP Volley 13 pour l’arbitrage et les feuilles de match.',
            ],
        },
        {
            num: 4,
            title: 'Organisation du championnat',
            content: [
                'Le championnat est découpé en divisions (1, 2, 3, …) jouées sur deux demi-saisons.',
                'À chaque demi-saison, les deux derniers (sauf dans la dernière division) descendent et les deux premiers des divisions inférieures montent. Toute exception est communiquée au début de saison.',
                'Le titre départemental est attribué lors de la deuxième demi-saison.',
            ],
        },
        {
            num: 5,
            title: 'Rotation',
            content: [
                'Toutes les joueuses se succèdent au service. La rotation n’est obligatoire qu’au moment du service.',
                'Les postes fixes sont autorisés tant que l’ordre initial de service est conservé durant tout le set.',
            ],
        },
        {
            num: 6,
            title: 'Zones',
            content: [
                'Aucune distinction n’est faite entre zone avant et zone arrière : toutes les joueuses peuvent attaquer dans toutes les zones.',
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
                'Chaque joueuse peut être remplacée au cours d’un set puis reprendre sa place, dans la limite de quatre remplacements.',
            ],
        },
        {
            num: 9,
            title: 'Filet',
            content: ['Hauteur du filet : 2,24 m.'],
        },
        {
            num: 10,
            title: 'Prêt de joueuse',
            content: ['Le prêt de joueuse est autorisé conformément au règlement général UFOLEP Volley 13.'],
        },
        {
            num: 11,
            title: 'Pénétrations',
            content: ['Les fautes de pénétration ou de contact filet sont jugées selon les règles FFVB en vigueur.'],
        },
        {
            num: 12,
            title: 'Inscription au championnat',
            content: [
                'Nouvelle inscription à valider auprès de la CTSD avant le 30 septembre pour la première demi-saison et avant le 30 janvier pour la seconde.',
            ],
        },
        {
            num: 13,
            title: 'Fiche équipe',
            content: ['Chaque fiche équipe doit être signée par la capitaine adverse.'],
        }
    ]
});
