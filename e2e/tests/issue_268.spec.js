// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Endpoints REST d'administration réservés aux admins (issue #268)
 *
 * Avant le correctif, ces endpoints répondaient 200 à un appelant anonyme :
 * `usermanager/getUsers` renvoyait 142 comptes (empreintes de mots de passe
 * comprises) et `court/delete` exécutait réellement la suppression.
 *
 * Ces tests s'exécutent avec un contexte de requête neuf, sans cookie, pour
 * garantir qu'aucune session ne traîne.
 *
 * Le pendant « un admin passe toujours » n'est pas ici : les 41 autres specs
 * couvrent les parcours public, responsable d'équipe et responsable de club, et
 * échoueraient si la garde était trop large.
 */

// Échantillon représentatif : une lecture sensible, une lecture d'admin, et
// trois écritures destructives.
const ADMIN_ONLY = [
    { method: 'GET', path: '/rest/action.php/usermanager/getUsers' },
    { method: 'GET', path: '/rest/action.php/rank/getRanks' },
    { method: 'GET', path: '/rest/action.php/news/getAllNews' },
    { method: 'POST', path: '/rest/action.php/court/delete', form: { ids: '-1' } },
    { method: 'POST', path: '/rest/action.php/usermanager/deleteUsers', form: { ids: '-1' } },
    { method: 'POST', path: '/rest/action.php/team/delete', form: { ids: '-1' } },
];

// Endpoints partagés avec le front public : ils doivent rester ouverts.
const PUBLIC_OK = [
    '/rest/action.php/court/getGymnasiums',
    '/rest/action.php/competition/getCompetitions',
    '/rest/action.php/limitdate/getLimitDates',
];

test.describe('Issue #268 — autorisation des endpoints REST admin', () => {

    for (const { method, path, form } of ADMIN_ONLY) {
        test(`${method} ${path} refuse un appelant anonyme`, async ({ playwright }) => {
            const ctx = await playwright.request.newContext();
            try {
                const res = method === 'GET'
                    ? await ctx.get(path)
                    : await ctx.post(path, { form });

                expect(res.status(), `${method} ${path} doit être refusé`).toBe(403);

                const body = await res.json();
                expect(body.success).toBe(false);
                expect(body.message).toContain('administrateurs');
            } finally {
                await ctx.dispose();
            }
        });
    }

    for (const path of PUBLIC_OK) {
        test(`GET ${path} reste accessible sans session`, async ({ playwright }) => {
            const ctx = await playwright.request.newContext();
            try {
                const res = await ctx.get(path);
                expect(res.status(), `${path} ne doit pas avoir été verrouillé`).toBe(200);
            } finally {
                await ctx.dispose();
            }
        });
    }

    // L'absence de `password_hash` dans `sql/get_users.sql` est une assertion de
    // niveau source : elle vit dans AdminRestAuthzTest (PHPUnit), qui tourne dans
    // le conteneur php avec le dépôt sous la main. La vérifier ici obligerait à
    // monter tout le dépôt dans le conteneur Playwright, ce que seul un des deux
    // modes e2e fait — le test dépendrait alors du montage.
});
