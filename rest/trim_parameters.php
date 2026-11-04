<?php

/**
 * Blancs de début et de fin des saisies (issue #452).
 *
 * Copié depuis un tableur, un nom arrivait avec une tabulation devant
 * (`\tBONHOMME`) : l'import de sa licence ne le retrouvait plus par son nom et
 * créait un doublon. `rest/action.php` passe donc chaque paramètre texte d'une
 * écriture par ici avant d'appeler la méthode, tableaux compris.
 *
 * Blancs retirés : espaces, tabulations, retours à la ligne (`\s`), et les
 * séparateurs Unicode d'un copier-coller (espace insécable, BOM…). Le milieu de
 * la chaîne n'est pas touché.
 *
 * Les mots de passe sont laissés tels quels : un mot de passe existant qui
 * finit par un espace ne doit pas cesser de fonctionner.
 */

/** Paramètre laissé tel quel si son nom contient l'un de ces mots. */
const UNTRIMMED_PARAMETER_WORDS = array('password');

function trim_text(string $value): string
{
    return preg_replace('/^[\s\p{Z}\x{FEFF}\x{200B}]+|[\s\p{Z}\x{FEFF}\x{200B}]+$/u', '', $value) ?? trim($value);
}

function trim_text_parameters(array $parameters): array
{
    foreach ($parameters as $key => $value) {
        foreach (UNTRIMMED_PARAMETER_WORDS as $word) {
            if (stripos((string)$key, $word) !== false) {
                continue 2;
            }
        }
        if (is_array($value)) {
            $parameters[$key] = trim_text_parameters($value);
        } elseif (is_string($value)) {
            $parameters[$key] = trim_text($value);
        }
    }
    return $parameters;
}
