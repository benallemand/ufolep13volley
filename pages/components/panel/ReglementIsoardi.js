import { createRulesComponent } from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement coupe André Isoardi',
    subtitle: 'Coupe départementale masculine 6x6',
    lastUpdate: '24 octobre 2014',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'Coupe ouverte à toutes les équipes engagées dans les divisions du championnat masculin départemental 6x6.',
                'Depuis 2009, elle honore André Isoardi, responsable de l’association Marseille Est.',
            ],
        },
        {
            num: 2,
            title: 'Règles de jeu',
            content: [
                'Arbitrage, feuille de match et règles techniques se conforment au règlement FFVB et au règlement général UFOLEP Volley 13.',
            ],
        },
        {
            num: 3,
            title: 'Organisation de la coupe',
            content: [
                'Phase 1 : poules de brassage jouées pendant l’inter-saison, constituées par tirage au sort avec maximum deux équipes d’une même division par poule.',
                'Phase 2 : tableau à élimination directe disputé durant la seconde demi-saison du championnat ; le tableau est tiré au sort en même temps que les poules.',
            ],
        },
        {
            num: 4,
            title: 'Handicap',
            content: [
                'Handicap appliqué selon l’écart de divisions : l’équipe issue de la division la plus basse débute chacun des quatre premiers sets avec 2 points × nombre de divisions d’écart.',
                'Au tie-break, l’avantage correspond au nombre de divisions d’écart.',
            ],
        },
        {
            num: 5,
            title: 'Inscription à la coupe',
            content: [
                'Toutes les équipes des divisions masculines sont automatiquement inscrites ; un désistement doit être signalé à la CTSD avant le tirage.',
                'Divisions 1 à 3 : poules 1 à 5, les deux premiers accèdent aux 1/8èmes. Divisions 4 à 6 : poules suivantes, seuls les premiers et le meilleur deuxième sont qualifiés.',
            ],
        },
        {
            num: 6,
            title: 'Calendrier et date limite',
            content: [
                'Les rencontres de poule se jouent durant l’inter-saison ; le planning est figé par le tirage au sort.',
                'Le tableau final se dispute pendant la seconde demi-saison et le calendrier complet est communiqué au plus tard fin novembre (deux mois avant l’inter-saison).',
            ],
        },
        {
            num: 7,
            title: 'Filet',
            content: ['Hauteur réglementaire fixée à 2,43 m.'],
        },
        {
            num: 8,
            title: 'Prêt de joueur',
            content: ['La règle de prêt définie dans le règlement général UFOLEP Volley 13 s’applique pendant la coupe.'],
        },
        {
            num: 9,
            title: 'Composition des équipes',
            content: [
                'La composition correspond à celle de l’équipe engagée en championnat masculin départemental.',
                'En phase éliminatoire, les équipes doivent jouer à six ; effectif réduit interdit sous peine de forfait.',
            ],
        },
    ],
});
