export default {
    template: `
      <div class="container mx-auto p-4">
        <div class="grid grid-cols-1">
          <div class="w-full">
            <div class="mb-4">
              <div class="bg-info text-center p-2 rounded-lg">
                <h3 class="text-lg font-bold">
                  À télécharger
                </h3>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-1">
              <div class="p-1">
                <p class="text-center"><img src="../images/MainVolley.jpg"
                                            class="max-h-20 mx-auto"/>
                </p>
              </div>
              <div class="p-1"><a class="link link-primary" href="../infos_utiles/Media/ReglementFeminin.pdf"
                                  target="_blank">Règlement
                championnat
                féminin</a>(14 juin
                2012)
              </div>
              <div class="p-1"><a class="link link-primary" href="../infos_utiles/Media/ReglementIsoardi.pdf"
                                  target="_blank">Règlement
                coupe
                Isoardi</a>(15
                mai 2012)
              </div>
              <div class="p-1"><a class="link link-primary" href="../infos_utiles/Media/ReglementKouryHanna.pdf"
                                  target="_blank">Règlement
                coupe
                Khoury
                Hanna</a>(15
                mai 2012)
              </div>
              <div class="p-1"><a class="link link-primary" href="#generalRules" target="_blank">Règlement général</a>(01
                novembre 2015)
              </div>
              <div class="p-1"><a class="link link-primary" href="../infos_utiles/Media/ReglementMasculin.pdf"
                                  target="_blank">Règlement
                championnat
                masculin</a>(15
                mai 2012)
              </div>
              <div class="p-1"><a class="link link-primary" href="../infos_utiles/Media/ReglementChampionnatMixte.pdf"
                                  target="_blank">Règlement
                championnat mixte</a>(01
                novembre 2015)
              </div>
              <div class="p-1"><a class="link link-primary" href="https://get.vscore.ch/" target="_blank">bonus:
                application de saisie des
                scores</a>
              </div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-4 mt-4">
          <div class="w-full">
            <div class="mb-4">
              <div class="bg-info text-center p-2 rounded-lg">
                <h3 class="text-lg font-bold">
                  Informations utiles
                </h3>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Pendant les phases d'inscription</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>Inscrire votre équipe auprès du responsable UFOLEP Volley: <a class="link link-primary"
                                                                                    href="/register.php"
                                                                                    target="_blank">Inscription</a></li>
                  <li>Une fois le compte créé (email avec identifiants reçus), se connecter au site et saisir l'ensemble
                    des
                    informations nécessaires à la génération d'une fiche équipe.
                    Un guide d'utilisation est disponible ici : <a
                        class="link link-primary"
                        href="https://docs.google.com/document/d/1jhAsF6npsuR7Qgf9v0Yw_30NT26Mz4sjTlSrYvyDnGQ/edit?usp=sharing"
                        target="_blank">tuto</a>
                  </li>
                </ul>
              </div>
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Le jour du match</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>Se connecter et se rendre sur sa page <a class="link link-primary" href="/pages/my_page.html"
                                                               target="_blank">ufolep13volley</a></li>
                  <li>Un lien sur le match du jour permet de saisir les joueurs présents au match</li>
                  <li><b>S'il n'y a pas de photo, prévoir une pièce d'identité permettant d'identifier la personne</b>
                  </li>
                  <li>Lorsque les joueurs des 2 équipes ont été saisis, il faut signer la fiche équipe</li>
                </ul>
              </div>
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Après un match</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>Un lien sur le match du jour permet de saisir les scores du match</li>
                  <li>Lorsque les scores ont été saisis et vérifiés, il faut signer la feuille de match</li>
                  <li>Un lien sur le match du jour permet l'accès au formulaire du fair-play</li>
                </ul>
              </div>
            </div>
            <div class="mb-4 mt-4">
              <div class="bg-error text-center p-2 rounded-lg">
                <p class="font-bold">Comment...</p>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="p-2">
                <p class="bg-error text-center p-2 rounded-lg font-bold">se connecter au "Portail équipes" ?</p>
                <p class="mt-2">
                  Sur le site <a class="link link-primary" href="https://www.ufolep13volley.org/">https://www.ufolep13volley.org/</a>
                  Sélectionner "Connexion" (lien
                  direct).
                  Entrer l'identifiant et le mot de passe de son équipe (fournis en début d'année par la
                  commission ou le demander auprès de :
                  <a class="link link-primary" href="mailto:contact@ufolep13volley.org">contact@ufolep13volley.org</a>
                  Une fois correctement identifié, la connexion s'établit et vous pouvez réaliser les opérations
                  de votre choix (voir dans chacune des rubriques supra).
                </p>
              </div>
              <div class="p-2">
                <p class="bg-error text-center p-2 rounded-lg font-bold">trouver un numéro de licence ?</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>
                    Si vous avez transmis tous les éléments nécessaires à l'inscription d'un/e joueur/se au
                    siège de
                    l'Ufolep (<b>81 rue de la Maurelle 13013 Marseille</b>), il est possible de savoir
                    si
                    son numéro de licence est prêt en se connectant sur le site
                    <a class="link link-primary" href="http://www.affiligue.org/Login.aspx">http://www.affiligue.org/Login.aspx</a>.
                  </li>
                  <li>
                    Il vous faut alors l'identifiant de votre club et le mot de passe (informations que doit
                    avoir le responsable volley de votre club).
                  </li>
                  <li>
                    Il est aussi possible (à utiliser sans en abuser, et notamment pas en début de saison) de le
                    demander en téléphonant au <b>04 13 24 80 00</b>.
                  </li>
                </ul>
              </div>
            </div>
            <div class="mb-4 mt-4">
              <div class="bg-info text-center p-2 rounded-lg">
                <h3 class="text-lg font-bold">
                  Rappel des compétitions
                </h3>
                <span>VALABLE POUR TOUTES LES COMPETITIONS : <b>TOUT LE MONDE SERT !</b></span>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Championnats</p>
                <p class="mt-2">
                  Championnat masculin :
                </p>
                <ul class="list-disc pl-5 mt-1">
                  <li>filet à 2 m 43</li>
                  <li>6 joueurs sur le terrain (mixité acceptée).</li>
                </ul>
                <p class="mt-2">
                  Championnat féminin :
                </p>
                <ul class="list-disc pl-5 mt-1">
                  <li>filet à 2 m 24</li>
                  <li>4 joueuses sur le terrain (pas de mixité possible).</li>
                </ul>
                <p class="mt-2">
                  Championnat mixte :
                </p>
                <ul class="list-disc pl-5 mt-1">
                  <li>filet à 2 m 35 dans la dernière division, filet à 2 m 43 pour toutes les autres divisions.
                  </li>
                  <li>4 joueurs sur le terrain (mixité obligatoire en permanence).</li>
                </ul>
              </div>
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Coupe Isoardi</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>mêmes règles que le championnat masculin</li>
                  <li>système de handicap calculé selon le classement de la 2ème demi-saison du championnat
                    masculin
                  </li>
                  <li>inscription automatique des équipes du championnat masculin/mixte, désistement possible auprès de
                    la commission à :
                    <a class="link link-primary" href="mailto:contact@ufolep13volley.org">contact@ufolep13volley.org</a>
                  </li>
                </ul>
              </div>
              <div class="p-2">
                <p class="bg-success text-center p-2 rounded-lg font-bold">Coupe Khoury Hanna</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>filet à 2 m 35</li>
                  <li>4x4 mixte avec minimum 2 filles sur le terrain</li>
                  <li>matchs entre équipes spécialement constituées pour cette épreuve sur la base de deux filles
                    minimum sur le terrain pour quatre joueurs au total
                  </li>
                  <li>inscription sur le site via le <a class="link link-primary" href="/register.php" target="_blank">formulaire</a>
                  </li>
                </ul>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <div class="p-2">
                <p class="bg-warning text-center p-2 rounded-lg font-bold">Utile</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>
                    Dans les championnats, une année sportive est divisée en 2 demi-saisons
                    (novembre-janvier et mars-mai) durant lesquelles a lieu un match par semaine (entre
                    le lundi et
                    le vendredi) entre équipes d'une même division, joué soit à domicile, soit à
                    l'extérieur.
                  </li>
                  <li>
                    En février et mars, à l'inter-saison, ont lieu conjointement la coupe Isoardi et la
                    coupe Koury
                    Hanna.
                  </li>
                </ul>
              </div>
              <div class="p-2">
                <p class="bg-warning text-center p-2 rounded-lg font-bold">Utile</p>
                <ul class="list-disc pl-5 mt-2">
                  <li>
                    Lors de l'inter-saison des championnats ont lieu conjointement les phases
                    qualificatives de la coupe Isoardi et la coupe Koury Hanna. Le tirage au sort des
                    poules a lieu début janvier, les phases finales se déroulent en mai et juin.
                  </li>
                  <li>
                    Lors de la création des calendriers, la commission essaiera de prendre en compte les
                    contraintes de
                    gymnase/terrains. Nous demandons cependant aux clubs engageant plusieurs équipes sur une ou
                    deux
                    coupes de bien vouloir limiter le nombre d'équipes inscrites en fonction du nombre de
                    terrains dont
                    ils disposent pour recevoir. La règle est simple :
                  </li>
                  <ul class="list-disc pl-10 mt-1">
                    <li>
                      0 terrain : 1 équipe max
                    </li>
                    <li>
                      1 terrain : 2 équipes max
                    </li>
                    <li>
                      2 terrains : 4 équipes max
                    </li>
                    <li>
                      3 terrains : 6 équipes max
                    </li>
                    <li>
                      4 terrains : 8 équipes max etc....
                    </li>
                  </ul>
                  <li class="mt-2">
                    Chaque joueur(se) pourra s'inscrire dans plusieurs compétitions. Cependant, la commission ne
                    pourra pas tenir compte de cela lors de la création des calendriers. Vous prenez donc le risque
                    d'avoir
                    2 matchs programmés le même soir. Le nombre de reports possibles étant très limité pendant
                    l'inter-saison, vous prenez ainsi le risque d'accumuler les forfaits. La commission
                    n'octroiera aucun délai pour report lié à une double inscription en coupes. Nous recommandons
                    fortement aux joueurs et joueuses de choisir l'une ou l'autre des coupes.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    `,
    data() {
        return {};
    },
};