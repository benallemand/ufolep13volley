<?php
$title = "Classement général";
$code_competition = filter_input(INPUT_GET, 'code_competition');
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <TITLE> <?php echo $title; ?></TITLE>
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
    <script type="text/javascript" src="/js/ux/jszip.min.js"></script>
    <script type="text/javascript" src="/js/ux/FileSaver.js"></script>
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
            type="text/javascript" src="js/rank_for_cup.js"></script>
    <script type="text/javascript">
        var code_competition = "<?php echo $code_competition; ?>";
    </script>
</HEAD>
<BODY>
</BODY>
</HTML>