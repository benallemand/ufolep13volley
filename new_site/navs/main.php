<nav class="navbar navbar-default">
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li><a href="#/"><span class="glyphicon glyphicon-home"></span> Accueil</a></li>
                <li class="dropdown">
                    <a class="dropdown-toggle" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-calendar"></span>
                        Championnats
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        require_once __DIR__ . '/../../classes/CompetitionManager.php';
                        $manager = new CompetitionManager();
                        $manager->generate_menu('m');
                        $manager->generate_menu('f');
                        $manager->generate_menu('mo');
                        ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a class="dropdown-toggle" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-calendar"></span>
                        Coupes
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <?php
                        require_once __DIR__ . '/../../classes/CompetitionManager.php';
                        $manager = new CompetitionManager();
                        $manager->generate_menu('c');
                        $manager->generate_menu('cf');
                        $manager->generate_menu('kh');
                        $manager->generate_menu('kf');
                        ?>
                    </ul>
                </li>
                <li class="dropdown">
                    <a class="dropdown-toggle" role="button" data-toggle="dropdown"
                       aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-info-sign"></span>
                        Informations
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a href="#weekMatches"><span class="glyphicon glyphicon-fire"></span> Matchs de la
                            semaine</a>
                        </li>
                        <li><a href="#lastResults"><span class="glyphicon glyphicon-fire"></span> Derniers résultats</a>
                        </li>
                        <li><a href="#hallOfFame"><span class="glyphicon glyphicon-usd"></span> Palmarès</a></li>
                        <li><a href="#phonebooks"><span class="glyphicon glyphicon-book"></span> Annuaire</a></li>
                        <li><a href="#gymnasiums"><span class="glyphicon glyphicon-map-marker"></span> Gymnases</a></li>
                        <!--<li><a href="#"><span class="glyphicon glyphicon-calendar"></span> Agenda</a></li>-->
                        <li>
                            <a href="https://docs.google.com/document/d/1jhAsF6npsuR7Qgf9v0Yw_30NT26Mz4sjTlSrYvyDnGQ/edit?usp=sharing"
                               target="_blank"><span class="glyphicon glyphicon-info-sign"></span> Tuto Responsable
                                d'équipe</a></li>
                        <li><a href="#usefulInformations"><span class="glyphicon glyphicon-info-sign"></span> Infos
                            utiles</a></li>
                        <li><a href="#commission">Commission</a></li>
                        <li><a href="mailto:contact@ufolep13volley.org"><span
                                class="glyphicon glyphicon-envelope"></span>
                            contact@ufolep13volley.org</a></li>
                        <li class="dropdown-header"><h4>Liens</h4></li>
                        <li><a href="#webSites" target="_blank"><span class="glyphicon glyphicon-link"></span> Sites web
                            des clubs</a>
                        </li>
                        <li><a href="http://ufolep13.org/" target="_blank"><span
                                class="glyphicon glyphicon-link"></span> Site de
                            l'UFOLEP 13</a></li>
                        <li class="dropdown-header"><h4>Règlements</h4></li>
                        <li>
                            <a href="http://www.fivb.org/EN/Refereeing-Rules/documents/FIVB-Volleyball_Rules_2017-2020-FR-v01.pdf"
                               target="_blank">FIVB</a>
                        </li>
                        <li><a href="#generalRules" target="_blank">Général</a></li>
                        <li><a href="../infos_utiles/Media/ReglementFeminin.pdf" target="_blank">Championnat féminin</a>
                        </li>
                        <li><a href="../infos_utiles/Media/ReglementMasculin.pdf" target="_blank">Championnat
                            masculin</a></li>
                        <li><a href="../infos_utiles/Media/ReglementChampionnatMixte.pdf" target="_blank">Championnat
                            mixte</a></li>
                        <li><a href="../infos_utiles/Media/ReglementKouryHanna.pdf" target="_blank">Coupe Khoury
                            Hanna</a></li>
                        <li><a href="../infos_utiles/Media/ReglementIsoardi.pdf" target="_blank">Coupe Isoardi</a></li>
                        <li><a href="../infos_utiles/Media/ReglementCoupeFeminine6x6.pdf" target="_blank">Coupe Féminine
                            6x6</a></li>
                        <li class="dropdown-header"><h4>Bonus</h4></li>
                        <li><a href="../infos_utiles/Media/FeuilleMatch1page.pdf" target="_blank">Feuille de match</a>
                        </li>
                        <li><a href="#accident">Déclaration de sinistre</a></li>
                    </ul>
                </li>
            </ul>
            <div ng-include src="'navs/my_main.php'"></div>
        </div>
    </div>
</nav>
