<?php
require_once __DIR__ . '/classes/Generic.php';
require_once __DIR__ . '/classes/MatchManager.php';
try {
    $generic = new Generic();
    $user_details = $generic->getCurrentUserDetails();
    if ($user_details['profile_name'] !== 'RESPONSABLE_EQUIPE') {
        throw new Exception("Profil responsable d'équipe nécessaire !", 401);
    }
} catch (Exception $e) {
    header('Location: login.php', true, 401);
}
try {
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        throw new Exception("id_match non défini !");
    }
    $manager = new MatchManager();
    if (!$manager->is_match_update_allowed($id_match)) {
        throw new Exception("Vous n'êtes pas autorisé à modifier ce match !");
    }
} catch (Exception $e) {
    echo "Erreur ! " . $e->getMessage();
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE>Modification d'un match</TITLE>
    <META
            http-equiv="Content-Type"
            content="text/html; charset=utf-8"/>
    <link
            rel="shortcut icon"
            href="favicon.ico"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer"/>
    <link
            href="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/resources/theme-crisp-touch-all.css"
            rel="stylesheet"/>
    <link
            href="/cells.css"
            rel="stylesheet"/>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/ext-all.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/locale/locale-fr.js"
            type="text/javascript"></script>
    <script
            src="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/theme-crisp-touch.js"
            type="text/javascript"></script>
    <script
            type="text/javascript" src="js/match.js"></script>
    <script type="text/javascript">
        var id_match = <?php echo $id_match; ?>;
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>