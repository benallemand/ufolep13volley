<?php global $id_match; ?>
<ul class="menu menu-horizontal bg-base-200 rounded-box flex items-center justify-between">
    <li class="border-solid rounded-full border-4 border-indigo-500">
        <a href="javascript:history.back()">
            <span>retour</span><i class="fa-solid fa-arrow-left"></i>
        </a>
    </li>
    <li class="border-solid rounded-full border-4 border-indigo-500">
        <a href="/">
            <span>accueil</span><i class="fa-solid fa-house"></i>
        </a>
    </li>
    <li class="border-solid rounded-full border-4 border-indigo-500">
        <a href="/match.php?id_match=<?php echo $id_match; ?>">
            <span>match</span><i class="fa-solid fa-volleyball"></i>
        </a>
    </li>
    <li class="border-solid rounded-full border-4 border-indigo-500">
        <a href="/team_sheets.php?id_match=<?php echo $id_match; ?>">
            <span>equipes</span><i class="fa-solid fa-user"></i>
        </a>
    </li>
    <li class="border-solid rounded-full border-4 border-indigo-500">
        <a href="/survey.php?id_match=<?php echo $id_match; ?>">
            <span>fair-play</span><i class="fa-solid fa-square-poll-vertical"></i>
        </a>
    </li>
</ul>
