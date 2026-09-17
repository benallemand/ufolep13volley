<?php

/**
 * Encodage des chaînes écrites dans un PDF FPDF.
 *
 * Les polices « core » de FPDF (Arial, Helvetica, Times…) ne sont pas des
 * polices Unicode : elles n'adressent qu'un seul octet par caractère, dans
 * **Windows-1252**. Toute chaîne venant de la base — donc de l'UTF-8 — doit
 * être convertie avant d'être passée à `Text()`, `Cell()` ou `MultiCell()`.
 *
 * **Windows-1252, pas ISO-8859-1.** Les deux jeux ne diffèrent que sur la
 * plage 0x80–0x9F, mais c'est précisément là que vivent les caractères de
 * ponctuation typographique que les navigateurs et les claviers produisent
 * spontanément :
 *
 * | Caractère | Windows-1252 | ISO-8859-1 |
 * |---|---|---|
 * | `’` apostrophe courbe | 0x92 | **absent** |
 * | `—` tiret cadratin | 0x97 | **absent** |
 * | `…` points de suspension | 0x85 | **absent** |
 * | `€` | 0x80 | **absent** |
 * | `œ` | 0x9C | **absent** |
 *
 * Viser l'ISO-8859-1 faisait donc disparaître l'apostrophe du nom d'équipe
 * « Les Jeu’nettes » sur son diplôme, alors que l'accent de « Féminin »
 * passait sans problème — les deux jeux partagent la plage 0xA0–0xFF.
 *
 * **`//TRANSLIT` n'est pas un raffinement, c'est ce qui évite une chaîne
 * vide.** Sans lui, `iconv()` renvoie `false` dès le premier caractère
 * intraduisible, et c'est tout le champ qui disparaît — pas seulement le
 * caractère fautif. Depuis que la base accepte tout l'Unicode (issue #334),
 * un nom d'équipe peut contenir un emoji ou un `✓` : ils deviennent ici un
 * `?` visible, et l'espace insécable étroite que l'iPhone glisse avant un
 * `?` devient une insécable ordinaire.
 *
 * Un vrai rendu de ces caractères demanderait une police Unicode embarquée,
 * donc de quitter FPDF 1.x.
 */
class PdfText
{
    /**
     * @param mixed $value valeur UTF-8 (ou null) venant de la base
     * @return string chaîne encodée en Windows-1252, prête pour FPDF
     */
    public static function encode(mixed $value): string
    {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }
        // @ : iconv émet une notice sur un caractère intraduisible, qui
        // polluerait le flux binaire du PDF déjà commencé.
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        if ($converted === false) {
            // Repli : mieux vaut perdre un caractère que le champ entier.
            $converted = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        }
        return $converted === false ? '' : $converted;
    }
}
