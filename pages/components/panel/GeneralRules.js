export default {
    template: `
      <div class="container mx-auto p-4">
        <div class="bg-base-100 shadow-xl rounded-lg p-4 mb-6">
          <h1 class="text-3xl font-bold text-center">Règlement général</h1>
          <h2 class="text-xl text-center mt-2">Chaque équipe doit l'appliquer et posséder un exemplaire récent du
            règlement de la Fédération Française de Volley Ball (F.F.V.B.)</h2>
          <h3 class="text-lg text-center mt-2">Dernière mise à jour le : <span class="font-bold">30 Août 2025</span>
          </h3>
        </div>
        <table
            class="mb-6 table w-full bg-base-100 border border-base-300 divide-y divide-base-300 [&_td:first-child::first-letter]:font-bold [&_td:first-child::first-letter]:uppercase">
          <tr>
            <th>Récapitulatif</th>
            <th>Article</th>
          </tr>
          <tr>
            <td>Adresses électroniques</td>
            <td><a href="#article-24" class="link link-primary">24</a></td>
          </tr>
          <tr>
            <td>Arbitrage</td>
            <td><a href="#article-2" class="link link-primary">2</a></td>
          </tr>
          <tr>
            <td>Assurances</td>
            <td><a href="#article-26" class="link link-primary">26</a></td>
          </tr>
          <tr>
            <td>Attribution des points</td>
            <td><a href="#article-12" class="link link-primary">12</a></td>
          </tr>
          <tr>
            <td>Classement</td>
            <td><a href="#article-13" class="link link-primary">13</a></td>
          </tr>
          <tr>
            <td>Composition d'équipe</td>
            <td><a href="#article-23" class="link link-primary">23</a></td>
          </tr>
          <tr>
            <td>Date envoi</td>
            <td><a href="#article-7" class="link link-primary">7</a></td>
          </tr>
          <tr>
            <td>Envoi feuille de match</td>
            <td><a href="#article-8" class="link link-primary">8</a></td>
          </tr>
          <tr>
            <td>Equipe complète, à effectif réduit ou incomplète</td>
            <td><a href="#article-11" class="link link-primary">11</a></td>
          </tr>
          <tr>
            <td>Faute au filet</td>
            <td><a href="#article-15" class="link link-primary">15</a></td>
          </tr>
          <tr>
            <td>Feuille de match</td>
            <td><a href="#article-5" class="link link-primary">5</a></td>
          </tr>
          <tr>
            <td>Frais inscription</td>
            <td><a href="#article-25" class="link link-primary">25</a></td>
          </tr>
          <tr>
            <td>Intervention pendant le match</td>
            <td><a href="#article-3" class="link link-primary">3</a></td>
          </tr>
          <tr>
            <td>Libéro</td>
            <td><a href="#article-21" class="link link-primary">21</a></td>
          </tr>
          <tr>
            <td>Licenciés FSGT et FFVB</td>
            <td><a href="#article-14" class="link link-primary">14</a></td>
          </tr>
          <tr>
            <td>Litiges</td>
            <td><a href="#article-22" class="link link-primary">22</a></td>
          </tr>
          <tr>
            <td>Présentation des licences</td>
            <td><a href="#article-10" class="link link-primary">10</a></td>
          </tr>
          <tr>
            <td>Prêt de joueur</td>
            <td><a href="#article-20" class="link link-primary">20</a></td>
          </tr>
          <tr>
            <td>Rencontres</td>
            <td><a href="#article-19" class="link link-primary">19</a></td>
          </tr>
          <tr>
            <td>Réclamations</td>
            <td><a href="#article-23" class="link link-primary">23</a></td>
          </tr>
          <tr>
            <td>Sanctions</td>
            <td><a href="#article-24" class="link link-primary">24</a></td>
          </tr>
        </table>
        <div class="grid grid-cols-[repeat(auto-fit,minmax(320px,1fr))] gap-4">
          <div id="article-1" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 1 : Saison sportive</h4>
            <ul class="list-disc pl-5">
              <li>Championnat départemental masculin 6 x 6 (mixte possible)</li>
              <li>Championnat départemental féminin 4 x 4</li>
              <li>Championnat départemental mixte 4 x 4 (mixité obligatoire)</li>
              <li>Coupe départementale masculine 6 x 6 (mixte possible)</li>
              <li>Coupe départementale mixte 4 x 4 (2 filles sur le terrain)</li>
            </ul>
          </div>
          <div id="article-2" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 2 : Arbitrage</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Une partie doit être arbitrée par une personne mandatée par l’équipe qui reçoit ou, à défaut, par
                l’équipe visiteuse après accord réciproque.
              </li>
              <li>L’arbitrage peut être assuré par une personne différente à chaque set.</li>
              <li>Il est recommandé d’assister l’arbitre par un second arbitre et des juges de ligne.</li>
              <li>L’arbitre retenu pour un set devient le responsable officiel du match et est souverain dans ses
                décisions.
              </li>
              <li>Sanctions possibles : perte du service, expulsion temporaire ou définitive du joueur fautif.</li>
              <li>Toute sanction doit être mentionnée sur la feuille de match.</li>
              <li>En l’absence d’arbitre, l’auto-arbitrage est possible par accord entre équipes.</li>
            </ul>
          </div>
          <div id="article-3" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 3 : Intervention pendant le match</h4>
            <p>Seul le Capitaine est autorisé à intervenir auprès de l’arbitre en cours de match.</p>
          </div>
          <div id="article-4" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 4 : Validation du match</h4>
            <p>Un match peut être validé par la commission si:</p>
            <ul class="list-disc pl-5 mb-2">
              <li>chaque responsable a rempli sa fiche d'équipe</li>
              <li>chaque responsable a signé les fiches d'équipe</li>
              <li>l'un des responsables a rempli la feuille de match</li>
              <li>chaque responsable a signé la feuille de match</li>
            </ul>
          </div>
          <div id="article-5" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 5 : Feuille de match</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>La feuille de match doit être remplie et vérifiée par les deux équipes.</li>
              <li>Une feuille de match incorrectement remplie peut entraîner des sanctions.</li>
              <li>Un joueur sans numéro de licence le soir du match entraîne des sanctions.</li>
              <li>Avant signature, tout commentaire peut être inscrit sur la feuille de match.</li>
              <li>La signature des responsables d'équipe vaut approbation du contenu de la feuille de match.</li>
            </ul>
          </div>
          <div id="article-6" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 6 : Saisie des résultats</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>L’équipe vainqueur doit saisir le résultat sur le site dans les 10 jours, sinon elle s'expose à des
                sanctions.
              </li>
              <li>L’équipe perdante doit vérifier la saisie, sinon elle doit la faire dans les 10 jours.</li>
              <li>L’idée est de donner un maximum de visibilité des résultats aux autres équipes engagées dans la
                compétition.
              </li>
            </ul>
          </div>
          <div id="article-7" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 7 : Date envoi</h4>
            <p>Les dates des actions réalisées sur le site web (remplir, signer) font foi.</p>
          </div>
          <div id="article-8" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 8 : Envoi feuille de match</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Les feuilles de match doivent parvenir au responsable des classements avant la date limite.</li>
              <li>En cas de retard, les deux équipes peuvent être sanctionnées.</li>
              <li>En cas de forfait, remplir le score du match (25-0 sur 3 sets), signer la feuille de match, et
                prévenir le responsable des classements.
              </li>
            </ul>
          </div>
          <div id="article-9" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 9 : Report</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Le jour du match est définitif à partir du moment où il est "confirmé"</li>
              <li>Les responsables d'équipe doivent se contacter 48h avant le match, pour confirmer la date.</li>
              <li>Si les 2 équipes sont d'accord pour jouer le match à une autre date, elles peuvent informer le
                responsable des classements de la nouvelle date du match, sans passer par une demande de report.
              </li>
              <li>Un report peut être demandé par un responsable d'équipe, sous réserve que la demande soit
                faite au moins 48h avant le match
              </li>
              <li>Un cas de force majeure (ex: réquisition de gymnase) force l'acceptation du report par
                l’équipe adverse.
              </li>
              <li>L'équipe qui accepte le report peut proposer la réception dans son gymnase si elle le souhaite.</li>
              <li>Un seul report maximum par demi-saison et par équipe.</li>
              <li>La nouvelle date doit être communiquée sous 10 jours au responsable des classements.</li>
              <li>Tout manquement peut être sanctionné.</li>
            </ul>
          </div>
          <div id="article-10" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 10 : Présentation des licences</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Un joueur peut participer si, à la date du match, son adhésion sur le site affiligue est "en cours"
                (dépôt complet non traité par affiligue) ou "validée" (dépôt complet traité par affiligue).
              </li>
              <li>La fiche équipe doit faire apparaitre les joueurs présents avec leur photo</li>
              <li>La signature des responsables atteste de la conformité des joueurs présents.</li>
              <li>Tout manquement peut être sanctionné.</li>
            </ul>
          </div>
          <div id="article-11" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 11 : Équipe complète, à effectif réduit ou incomplète</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Un match peut se jouer avec 5 joueurs dans une compétition 6x6, 3 joueurs dans une compétition 4x4
                (effectif réduit).
              </li>
              <li>En cas de blessure, l'équipe peut finir le match à effectif réduit.</li>
              <li>En dessous de l'effectif réduit (< 5 joueurs en 6x6, < 3 en 4x4), l'équipe est incomplète et est
                déclarée forfait.
              </li>
              <li>Un joueur peut enchainer 2 matchs le même soir seulement si le 2e match n'a pas encore commencé au
                moment où le premier match se termine.
              </li>
            </ul>
          </div>
          <div id="article-12" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 12 : Attribution des points</h4>
            <table class="table table-compact w-full mb-2">
              <tr>
                <td>Victoire (même effectif réduit)</td>
                <td>3 points</td>
              </tr>
              <tr>
                <td>Défaite</td>
                <td>1 point</td>
              </tr>
              <tr>
                <td>Forfait</td>
                <td>0 point</td>
              </tr>
            </table>
            <p>Forfait général possible en cas de forfaits répétés.</p>
          </div>
          <div id="article-13" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 13 : Classement</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Classement par points, puis différence sets gagnés/perdus, puis résultat de la rencontre
                directe.
              </li>
              <li>En cas d'égalité à 3, différence sets/points entre les 3 équipes, puis fair-play.</li>
            </ul>
          </div>
          <div id="article-14" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 14 : Licenciés FSGT et FFVB</h4>
            <p>Le championnat UFOLEP est prioritaire sur les autres compétitions. Aucun report ne sera accordé pour
              ce motif.</p>
          </div>
          <div id="article-15" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 15 : Faute au filet</h4>
            <p>Comme en FFVB, toute faute de contact avec le filet entraîne le gain du point par l'adversaire et l'arrêt
              immédiat de l'échange.</p>
          </div>
          <div id="article-16" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 16 : Référence règlement</h4>
            <p>Le règlement FIVB fait autorité sauf exceptions précisées ici.</p>
            <p>Certains éléments (mires, 3 ballons, etc.) sont facultatifs.</p>
          </div>
          <div id="article-17" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 17 : Victoire match</h4>
            <p>Un match est gagné par l'équipe qui remporte trois sets.</p>
          </div>
          <div id="article-18" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 18 : Victoire set</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Un set est gagné à 25 points avec 2 points d'écart.</li>
              <li>En cas d'égalité 24/24, le jeu continue jusqu'à 2 points d'écart.</li>
              <li>Set décisif à 15 points, sans limite si égalité à 14/14.</li>
            </ul>
          </div>
          <div id="article-19" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 19 : Rencontres</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Les rencontres se déroulent selon le calendrier officiel.</li>
              <li>Tout report doit être validé par la commission.</li>
            </ul>
          </div>
          <div id="article-20" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 20 : Prêt de joueur</h4>
            <p>Le prêt de joueur (renfort) est autorisé sous certaines conditions:</p>
            <ul class="list-disc pl-5 mb-2">
              <li>Une équipe peut faire un prêt pour être au maximum 8 joueurs (en 6x6) ou 5 joueurs (en 4x4) sur la
                fiche d'équipe.
              </li>
              <li>Une équipe ne peut avoir qu'un seul joueur prêté par match.</li>
              <li>Un joueur prêté ne peut l'être qu'une fois par demi-saison.</li>
              <li>Un joueur prêté ne doit pas évoluer dans une division supérieure à celle de l'équipe demandant le
                prêt.
              </li>
            </ul>
          </div>
          <div id="article-21" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 21 : Libéro</h4>
            <p>L’utilisation du libéro est possible en compétition 6x6, en respectant le règlement FIVB adapté au
              championnat.</p>
            <p>Il est possible de changer de libéro entre chaque set.</p>
          </div>
          <div id="article-22" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 22 : Litiges</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Tout litige doit être signalé à la commission dans les 48h.</li>
              <li>La commission statue en dernier ressort.</li>
            </ul>
          </div>
          <div id="article-23" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 23 : Réclamations</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Toute réclamation doit être formulée par écrit et transmise à la commission.</li>
              <li>Un droit de réclamation peut être exigé.</li>
            </ul>
          </div>
          <div id="article-24" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 24 : Sanctions</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Les sanctions sont prononcées par la commission selon la gravité des faits.</li>
              <li>Un barème des sanctions est disponible sur demande.</li>
            </ul>
          </div>
          <div id="article-25" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 25 : Frais inscription</h4>
            <ul class="list-disc pl-5 mb-2">
              <li>Les frais d'inscription sont de 10€ pour une équipe 6x6, 5€ pour une équipe 4x4.</li>
              <li>Ces frais sont dissociés de l'affiliation Ufolep.</li>
              <li>Un rappel automatique est envoyé chaque semaine aux responsables de club qui n'ont pas réglé ces
                frais.
              </li>
              <li>Ces frais d'inscription sont à régler directement à l'Ufolep13.</li>
              <li>Une fois les frais d'inscription réglés, informer la commission pour arrêter le rappel automatique.
              </li>
              <li>Une équipe qui s'inscrit en 2e demi-saison n'a pas de frais d'engagement</li>
              <li>Une équipe qui s'inscrit en coupe n'a pas de frais d'engagement</li>
            </ul>
          </div>
          <div id="article-26" class="bg-base-200 rounded-xl p-4">
            <h4 class="font-bold text-lg mb-2">Article 26 : Assurances</h4>
            <p>Chaque club doit vérifier que ses joueurs sont bien assurés pour la pratique du volley-ball en
              compétition.</p>
          </div>
        </div>
      </div>
    `,
    data() {
        return {};
    },
};