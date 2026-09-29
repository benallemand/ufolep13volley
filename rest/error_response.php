<?php

/**
 * Traduction d'une exception en réponse HTTP pour le client (issue #355).
 *
 * Sans `mysqli_report()` explicite, PHP 8 lève une `mysqli_sql_exception`
 * portant le message brut de MySQL — et son errno comme code. Le routeur
 * renvoyait l'un en JSON et l'autre en code HTTP (`http_response_code(1062)`) :
 * chaque injection devenait un oracle d'erreur, et l'utilisateur recevait un
 * message technique.
 *
 * Ici :
 *   - erreur SQL : détail journalisé côté serveur avec une référence, message
 *     générique au client — sauf doublon et contrainte d'intégrité, reformulés ;
 *   - autre exception : message conservé (ce sont les messages métier) ;
 *   - code HTTP invalide : 500.
 *
 * @return array{0: int, 1: string} [code HTTP, message pour le client]
 */
function client_error_response(Throwable $exception): array
{
    $message = $exception->getMessage();
    $is_sql = $exception instanceof mysqli_sql_exception
        || str_starts_with($message, 'Erreur SQL');
    if ($is_sql) {
        $errno = $exception instanceof mysqli_sql_exception ? (int)$exception->getCode() : 0;
        if ($errno === 1062 || str_contains($message, 'Duplicate entry')) {
            if (preg_match("/Duplicate entry '(.*)' for key/s", $message, $m)) {
                return array(409, "La valeur « " . htmlspecialchars($m[1], ENT_QUOTES) . " » existe déjà !");
            }
            return array(409, "Cette valeur existe déjà !");
        }
        if (in_array($errno, array(1451, 1452), true)
            || str_contains($message, 'foreign key constraint fails')) {
            return array(409, "Opération impossible : des données liées s'y opposent !");
        }
        $reference = bin2hex(random_bytes(4));
        error_log("[réf. $reference] " . get_class($exception) . ": $message");
        return array(500, "Erreur de base de données (réf. $reference).");
    }
    $code = (int)$exception->getCode();
    if ($code < 200 || $code > 599) {
        $code = 500;
    }
    return array($code, $message);
}
