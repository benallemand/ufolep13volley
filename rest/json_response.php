<?php

/**
 * Réponse JSON de `rest/action.php` (issue #433).
 *
 * Sans en-tête, le serveur l'envoyait en `text/html` : le jour où un message
 * d'erreur ou une donnée renvoyée contiendrait du HTML tiré de la requête, le
 * navigateur l'interpréterait (XSS par réflexion). D'où, pour chaque réponse :
 * - le bon `Content-Type`, et `nosniff` pour que le navigateur s'y tienne ;
 * - `JSON_HEX_TAG` : `<` et `>` écrits en échappement Unicode JSON (code
 *   hexadécimal 3C et 3E), ce qu'un client JSON relit à l'identique.
 *
 * Les actions qui renvoient un fichier (calendrier, PDF, photo) envoient leurs
 * propres en-têtes et sortent avant : rien n'est réécrit après coup.
 */
function json_response_body(mixed $data): string
{
    return json_encode($data, JSON_HEX_TAG);
}

function send_json(mixed $data): void
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_response_body($data);
}
