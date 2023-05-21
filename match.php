<?php
require_once __DIR__ . '/classes/Generic.php';
require_once __DIR__ . '/classes/MatchMgr.php';
try {
    $generic = new Generic();
    $user_details = $generic->getCurrentUserDetails();
    if (!in_array($user_details['profile_name'], array('RESPONSABLE_EQUIPE', 'ADMINISTRATEUR'))) {
        throw new Exception("Profil responsable d'équipe ou administrateur nécessaire !", 401);
    }
} catch (Exception $e) {
    header('Location: /new_site/#/login?redirect=' . filter_input(INPUT_SERVER, 'REQUEST_URI') . '&reason=' . $e->getMessage());
    exit(0);
}
try {
    $id_match = filter_input(INPUT_GET, 'id_match');
    if (empty($id_match)) {
        throw new Exception("id_match non défini !");
    }
    $manager = new MatchMgr();
    if (!$manager->is_match_update_allowed($id_match)) {
        throw new Exception("Vous n'êtes pas autorisé à modifier ce match !");
    }
} catch (Exception $e) {
    echo "Erreur ! " . $e->getMessage();
}
@session_start();
$user_details = $_SESSION;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
          integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
          crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <link
            href="//cdnjs.cloudflare.com/ajax/libs/extjs/6.2.0/classic/theme-crisp-touch/resources/theme-crisp-touch-all.css"
            rel="stylesheet"/>
    <link
            href="/cells.css"
            rel="stylesheet"/>
    <link
            href="includes/main.css"
            rel="stylesheet"
            type="text/css"
            media="screen"/>
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
        var user_details = <?php echo json_encode($user_details); ?>;
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>