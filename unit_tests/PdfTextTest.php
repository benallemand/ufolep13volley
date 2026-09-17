<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/PdfText.php';

use PHPUnit\Framework\TestCase;

/**
 * Encodage des chaînes écrites dans un PDF FPDF.
 *
 * Le défaut d'origine : le diplôme de l'équipe « Les Jeu’nettes » sortait sans
 * son apostrophe. La conversion visait l'**ISO-8859-1**, alors que les polices
 * « core » de FPDF lisent du **Windows-1252**. Les deux jeux ne diffèrent que
 * sur la plage 0x80–0x9F — mais c'est là que vivent toute la ponctuation
 * typographique et l'euro.
 *
 * Les attendus sont écrits en **octets** : c'est ce que FPDF reçoit, et une
 * comparaison de chaînes ne dirait pas dans quel jeu on est tombé.
 *
 * Aucune base de données ici — d'où `TestCase` plutôt que `UfolepTestCase`.
 */
class PdfTextTest extends TestCase
{
    /**
     * Les cinq caractères que l'ISO-8859-1 ne connaît pas et que Windows-1252
     * place entre 0x80 et 0x9F. Le premier est celui du ticket.
     */
    public function test_la_ponctuation_typographique_garde_sa_place_cp1252(): void
    {
        $attendus = [
            'apostrophe courbe' => ["\u{2019}", "\x92"],
            'tiret cadratin' => ["\u{2014}", "\x97"],
            'points de suspension' => ["\u{2026}", "\x85"],
            'euro' => ["\u{20AC}", "\x80"],
            'o e lie' => ["\u{0153}", "\x9C"],
        ];

        foreach ($attendus as $libelle => [$entree, $octet]) {
            self::assertSame($octet, PdfText::encode($entree), $libelle);
        }
    }

    /** Le nom d'équipe du ticket, de bout en bout. */
    public function test_le_nom_d_equipe_du_ticket_garde_son_apostrophe(): void
    {
        self::assertSame(
            "Les Jeu\x92nettes",
            PdfText::encode("Les Jeu\u{2019}nettes"));
    }

    /**
     * La plage 0xA0–0xFF est commune aux deux jeux : l'accent passait déjà, et
     * doit continuer à passer à l'identique.
     */
    public function test_les_accents_sont_inchanges(): void
    {
        self::assertSame("F\xE9minin", PdfText::encode("F\u{00E9}minin"));
        self::assertSame("Bouches-du-Rh\xF4ne", PdfText::encode("Bouches-du-Rh\u{00F4}ne"));
    }

    /**
     * Le vrai piège d'`iconv` : sans `//TRANSLIT`, un seul caractère
     * intraduisible fait renvoyer `false`, et c'est le champ ENTIER qui
     * disparaît. Depuis que la base accepte tout l'Unicode (issue #334), un
     * nom d'équipe peut en contenir.
     */
    public function test_un_caractere_intraduisible_ne_vide_pas_le_champ(): void
    {
        $cas = [
            'emoji' => "Volley \u{1F3D0}",
            'check' => "Vainqueur \u{2713}",
        ];

        foreach ($cas as $libelle => $entree) {
            $encode = PdfText::encode($entree);
            self::assertNotSame('', $encode, "$libelle : le champ ne doit pas disparaitre");
            self::assertStringStartsWith(
                $libelle === 'emoji' ? 'Volley ' : 'Vainqueur ', $encode, $libelle);
        }
    }

    /**
     * L'espace insécable étroite qu'iOS en français glisse avant un « ? » n'a
     * pas d'équivalent en Windows-1252 ; elle doit devenir une insécable
     * ordinaire, pas un « ? » ni rien.
     */
    public function test_l_espace_etroite_devient_une_insecable(): void
    {
        self::assertSame("Fini\xA0?", PdfText::encode("Fini\u{202F}?"));
    }

    /**
     * `null` vient de la base sur toute colonne nullable, et le passer à une
     * fonction interne est déprécié depuis PHP 8.1.
     */
    public function test_les_valeurs_vides_ne_levent_rien(): void
    {
        self::assertSame('', PdfText::encode(null));
        self::assertSame('', PdfText::encode(''));
    }

    /**
     * L'ancien `!empty($string)` rendait `''` pour la chaîne « 0 » : un
     * décompte nul s'affichait comme une case vide.
     */
    public function test_le_zero_n_est_pas_une_chaine_vide(): void
    {
        self::assertSame('0', PdfText::encode('0'));
        self::assertSame('0', PdfText::encode(0));
    }
}
