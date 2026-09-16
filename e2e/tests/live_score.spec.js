// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Live Score, mode arbitre (issues #217 puis #332)
 *
 * L'acquis de #217 était « les boutons de marque sont collés au score et
 * visibles sans défiler ». #332 le pousse plus loin : les boutons ONT DISPARU,
 * ce sont les deux moitiés de l'écran qui marquent. La garantie vérifiée ici
 * reste la même — on marque sans jamais faire défiler l'écran — et le scénario
 * de bout en bout (2 sets reportés dans le match) est inchangé.
 *
 * Scénario :
 *  1. Créer un match dont la date de réception est aujourd'hui
 *  2. Démarrer le live score
 *  3. Vérifier que les deux zones de marque occupent l'écran, sans défilement
 *  4. Saisir 2 sets complets
 *  5. Enregistrer dans le match via « Renseigner les scores »
 *  6. Vérifier en base que les scores des 2 sets sont bien reportés
 */

let codeMatch = null;

// Créer le match en base avant tous les tests (via APIRequestContext sans navigateur)
test.beforeAll(async ({ request }) => {
    const res = await request.get('/e2e/helpers/test_setup.php');
    expect(res.status(), 'Le script de setup doit répondre 200').toBe(200);

    const body = await res.json();
    expect(body.error, `Erreur setup : ${body.error}`).toBeUndefined();
    expect(body.code_match).toBeTruthy();
    codeMatch = body.code_match;
});

// Nettoyer le match de test après tous les tests
test.afterAll(async ({ request }) => {
    if (codeMatch) {
        await request.get(`/e2e/helpers/test_teardown.php?code_match=${codeMatch}`);
    }
});

test('live score : marque sans défilement + 2 sets reportés dans le match', async ({ page }) => {
    // ------------------------------------------------------------------ //
    // 1. Établir la session admin en naviguant vers le script de setup.
    //    PHP retourne un Set-Cookie PHPSESSID → le browser le stocke.
    // ------------------------------------------------------------------ //
    await page.goto('/e2e/helpers/test_setup.php');
    const setupData = JSON.parse(await page.locator('body').innerText());
    expect(setupData.error, `Erreur setup : ${setupData.error}`).toBeUndefined();
    // Le goto recrée un nouveau match — on met à jour codeMatch
    codeMatch = setupData.code_match;

    // ------------------------------------------------------------------ //
    // 2. Ouvrir la page live en mode scoreur
    // ------------------------------------------------------------------ //
    await page.goto(`/live.html?id_match=${codeMatch}&mode=scorer`);

    // La page doit afficher le badge de compétition (app chargée)
    await expect(page.locator('.badge-info').first()).toBeVisible();

    // ------------------------------------------------------------------ //
    // 3. Démarrer le live score
    // ------------------------------------------------------------------ //
    const startBtn = page.getByRole('button', { name: /Démarrer le Live Score/i });
    await expect(startBtn).toBeVisible();

    const [startResponse] = await Promise.all([
        page.waitForResponse(resp =>
            resp.url().includes('/ajax/live_score.php') && resp.request().method() === 'POST'
        ),
        startBtn.click(),
    ]);
    const startJson = await startResponse.json();
    expect(startJson.success, 'startLiveScore doit réussir').toBe(true);

    // ------------------------------------------------------------------ //
    // 4. Les deux zones de marque, visibles sans défilement (#217 → #332)
    // ------------------------------------------------------------------ //
    const zones = page.getByRole('button', { name: /^Point pour / });
    await expect(zones).toHaveCount(2);

    const leftZone = zones.nth(0);
    const rightZone = zones.nth(1);
    await expect(leftZone, 'Zone de marque gauche dans la viewport').toBeInViewport();
    await expect(rightZone, 'Zone de marque droite dans la viewport').toBeInViewport();

    // La page elle-même ne défile pas : l'écran arbitre est en position fixe.
    const pageScrolls = await page.evaluate(
        () => document.body.scrollHeight > window.innerHeight + 1
    );
    expect(pageScrolls, "L'écran arbitre ne doit pas faire défiler la page").toBe(false);

    // Chaque zone est plus grande que l'ancien bouton de 64 px : c'est l'objet
    // de la refonte, on le mesure au lieu de le supposer.
    const leftBox = await leftZone.boundingBox();
    expect(leftBox.height, 'La zone de marque doit rester très grande').toBeGreaterThan(200);

    await page.screenshot({ path: 'test-results/issue-332/proof-1-zones-de-marque.png', fullPage: false });

    // ------------------------------------------------------------------ //
    // 5. Saisir le set 1 : 3 points dom, 1 point ext → gagné par la gauche
    // ------------------------------------------------------------------ //
    await clickNTimes(leftZone, 3);
    await clickNTimes(rightZone, 1);

    await expect(leftZone).toContainText('3');
    await expect(rightZone).toContainText('1');

    // Les actions qui interrompent le jeu sont dans la feuille « Plus »
    await page.getByRole('button', { name: /Plus/ }).click();
    const nextSetLeftBtn = page.getByRole('button', { name: /^Set gagné par / }).nth(0);
    await expect(nextSetLeftBtn).toBeVisible();
    await nextSetLeftBtn.click();

    // ------------------------------------------------------------------ //
    // 6. « Renseigner les scores du match » apparaît dès 1 set joué
    //    (correction de l'issue #217 : masqué jusqu'à 3 sets auparavant)
    // ------------------------------------------------------------------ //
    await page.getByRole('button', { name: /Plus/ }).click();
    const saveToMatchBtn = page.getByRole('button', { name: /Renseigner les scores du match/i });
    await expect(saveToMatchBtn, '"Renseigner" visible après 1 set').toBeVisible();
    await page.getByRole('button', { name: /^Fermer$/ }).click();

    // ------------------------------------------------------------------ //
    // 7. Saisir le set 2 : 2 points dom, 4 points ext → gagné par la droite
    // ------------------------------------------------------------------ //
    await clickNTimes(leftZone, 2);
    await clickNTimes(rightZone, 4);

    await page.getByRole('button', { name: /Plus/ }).click();
    await page.getByRole('button', { name: /^Set gagné par / }).nth(1).click();

    // ------------------------------------------------------------------ //
    // 8. Enregistrer dans le match
    //    saveToMatch() : flush via upsert (si unsaved) + save_to_match
    // ------------------------------------------------------------------ //
    await page.getByRole('button', { name: /Plus/ }).click();

    // Accepter automatiquement le confirm() natif du navigateur
    page.once('dialog', dialog => dialog.accept());

    // On attend les 2 requêtes AJAX : upsert (flush) + save_to_match
    const ajaxDone = page.waitForResponse(
        resp => resp.url().includes('/ajax/live_score.php') && resp.status() === 200,
        { timeout: 15000 }
    );
    await page.getByRole('button', { name: /Renseigner les scores du match/i }).click();
    await ajaxDone;

    // Le toast de confirmation doit apparaître
    await expect(
        page.getByText('Scores enregistrés dans le match'),
        'Toast de confirmation attendu après saveToMatch'
    ).toBeVisible({ timeout: 10000 });

    // ------------------------------------------------------------------ //
    // 9. Vérifier en base que les 2 sets sont bien reportés dans `matches`
    // ------------------------------------------------------------------ //
    const verifyRes = await page.request.get(
        `/e2e/helpers/test_verify.php?code_match=${codeMatch}`
    );
    expect(verifyRes.status()).toBe(200);

    const scores = await verifyRes.json();
    expect(String(scores.set_1_dom), 'set_1_dom = 3').toBe('3');
    expect(String(scores.set_1_ext), 'set_1_ext = 1').toBe('1');
    expect(String(scores.set_2_dom), 'set_2_dom = 2').toBe('2');
    expect(String(scores.set_2_ext), 'set_2_ext = 4').toBe('4');

    // Sets 3-5 doivent rester à 0 / null
    const set3dom = Number(scores.set_3_dom ?? 0);
    expect(set3dom, 'set_3_dom doit être 0 (non renseigné)').toBe(0);

    await page.screenshot({ path: 'test-results/issue-332/proof-2-sets-reportes-dans-match.png', fullPage: true });
});

/**
 * La page ordinaire ne doit pas être rendue derrière le plein écran arbitre
 * (issue #332).
 *
 * Elle l'était, et la carte des infos du match repassait par-dessus les
 * feuilles : `position: fixed` crée toujours un contexte d'empilement, donc le
 * `z-index` des feuilles y était enfermé, et un élément positionné placé plus
 * bas dans le DOM peignait au-dessus. Sur un iPhone SE, la composition en
 * devenait inutilisable.
 *
 * Relevé en recette par Benjamin, sur un écran de 375 × 667.
 */
test("le plein écran arbitre ne laisse rien de la page ordinaire derrière lui", async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });

    await page.goto('/e2e/helpers/test_setup.php');
    const setupData = JSON.parse(await page.locator('body').innerText());
    expect(setupData.error, `Erreur setup : ${setupData.error}`).toBeUndefined();
    codeMatch = setupData.code_match;

    await page.goto(`/live.html?id_match=${codeMatch}&mode=scorer`);
    const startBtn = page.getByRole('button', { name: /Démarrer le Live Score/i });
    await expect(startBtn).toBeVisible();
    await Promise.all([
        page.waitForResponse(resp =>
            resp.url().includes('/ajax/live_score.php') && resp.request().method() === 'POST'
        ),
        startBtn.click(),
    ]);

    // Plus rien de la page ordinaire : ni barre de navigation, ni carte d'infos.
    await expect(page.getByRole('link', { name: /UFOLEP 13/ })).toHaveCount(0);
    await expect(page.getByText(/Mise à jour automatique/)).toHaveCount(0);

    // Date et gymnase sont devenus une information secondaire, dans la feuille.
    await page.getByRole('button', { name: /Plus/ }).click();
    await expect(page.getByText(/Gymnase|gymnase non défini/).first()).toBeVisible();

    // Et la feuille est bien cliquable de bout en bout : si quelque chose la
    // recouvrait, Playwright refuserait le clic au lieu de le simuler.
    await page.getByRole('button', { name: /Inverser les camps/ }).click();
});

/**
 * L'annulation doit défaire le service ET la rotation (issue #332).
 *
 * C'est le défaut qui a motivé le remplacement des deux `-1` par camp :
 * `decrementScore()` ne rendait que le point, et une reprise de service annulée
 * laissait l'équipe tournée d'un cran pour tout le reste du set.
 */
test("annuler un point rend le score et le service", async ({ page }) => {
    await page.goto('/e2e/helpers/test_setup.php');
    const setupData = JSON.parse(await page.locator('body').innerText());
    expect(setupData.error, `Erreur setup : ${setupData.error}`).toBeUndefined();
    codeMatch = setupData.code_match;

    await page.goto(`/live.html?id_match=${codeMatch}&mode=scorer`);
    const startBtn = page.getByRole('button', { name: /Démarrer le Live Score/i });
    await expect(startBtn).toBeVisible();
    await Promise.all([
        page.waitForResponse(resp =>
            resp.url().includes('/ajax/live_score.php') && resp.request().method() === 'POST'
        ),
        startBtn.click(),
    ]);

    const zones = page.getByRole('button', { name: /^Point pour / });
    const leftZone = zones.nth(0);
    const rightZone = zones.nth(1);
    const undoBtn = page.getByRole('button', { name: /Annuler/ });

    // Rien à annuler tant qu'aucun point n'a été marqué.
    await expect(undoBtn).toBeDisabled();

    // Gauche sert, puis la droite reprend le service : c'est ce side-out que
    // l'annulation doit défaire.
    await leftZone.click();
    await expect(leftZone).toContainText('SERVICE');
    await rightZone.click();
    await expect(rightZone).toContainText('SERVICE');

    await undoBtn.click();

    await expect(leftZone, 'le service revient à gauche').toContainText('SERVICE');
    await expect(rightZone, 'et quitte la droite').not.toContainText('SERVICE');
    await expect(rightZone, 'le point est rendu').toContainText('0');
});

// ------------------------------------------------------------------ //
// Utilitaire : cliquer N fois sur un locator
// ------------------------------------------------------------------ //
async function clickNTimes(locator, n) {
    for (let i = 0; i < n; i++) {
        await locator.click();
    }
}
