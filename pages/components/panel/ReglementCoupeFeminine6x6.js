import { createRulesComponent } from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement coupe féminine',
    subtitle: '6 x 6',
    lastUpdate: '27 décembre 2016',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'La CTSD UFOLEP Volley des Bouches du Rhône organise une coupe départementale féminine 6 x 6 attribuant un titre annuel.',
            ],
        },
        {
            num: 2,
            title: "Inscription d'une équipe",
            content: [
                'Une équipe comprend au moins six joueuses et la liste est figée pour toute la coupe ; seules les joueuses inscrites sur la fiche officielle peuvent participer.',
                'Les joueuses peuvent provenir de clubs différents, l’effectif réduit (match à 5) est autorisé.',
                'Chaque participante doit être licenciée UFOLEP ou adhérente d’un club affilié UFOLEP, même si elle possède une licence dans une autre fédération ; les licences en cours doivent être présentées le jour du match.',
            ],
        },
        {
            num: 3,
            title: 'Règles de jeu',
            content: [
                'Arbitrage et feuilles de match se réfèrent au règlement FFVB et au règlement général UFOLEP Volley 13.',
            ],
        },
        {
            num: 4,
            title: 'Organisation de la coupe',
            content: [
                'Compétition ouverte sous réserve d’un minimum de huit équipes inscrites.',
                'Des poules de brassage se jouent pendant l’inter-saison, suivies d’une phase éliminatoire dont la finale a lieu après la seconde demi-saison du championnat.',
                'La CTSD peut ajuster le format selon le nombre d’équipes.',
            ],
        },
        {
            num: 5,
            title: 'Handicap',
            content: ['Aucun handicap de points n’est appliqué (contrairement à la coupe Isoardi).'],
        },
        {
            num: 6,
            title: 'Filet',
            content: ['Hauteur réglementaire : 2,24 m.'],
        },
        {
            num: 7,
            title: 'Prêt de joueuse',
            content: ['Le prêt de joueuse est interdit pour cette coupe.'],
        },
        {
            num: 8,
            title: 'Pénétrations',
            content: ['Les fautes de pénétration ou de contact filet sont jugées selon les règles FFVB (référence 2010).'],
        },
        {
            num: 9,
            title: 'Inscription à la coupe',
            content: ['Les clubs doivent confirmer leur participation auprès de la CTSD avant fin décembre.'],
        },
        {
            num: 10,
            title: 'Remplacements',
            content: ['Chaque joueuse peut être remplacée au cours d’un set puis reprendre sa place, avec un maximum de quatre remplacements.'],
        },
        {
            num: 11,
            title: 'Fiche équipe',
            content: ['Chaque fiche équipe doit être signée par la capitaine adverse pour validation.'],
        },
    ],
});
