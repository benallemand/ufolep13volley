// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Suppression de l'endpoint orphelin /ajax/commission.php (issue #270)
 *
 * Cet endpoint n'était appelé par aucun frontend, mais restait servi en HTTP.
 * `Rest::parseRequest()` n'exigeait le rôle admin que pour les écritures : le GET
 * était ouvert à tous, et `Rest::getData()` concaténait les paramètres d'URL
 * directement dans le SQL —
 *   `$sql .= " $querible LIKE '%$whereClause%' "`
 *   `$sql .= " limit $startParam,$limitParam"`
 * — le `catch` renvoyant en prime la requête cassée à l'appelant
 * (`print_r($sql); exit(-1);`), ce qui fournissait un oracle d'erreur.
 *
 * `ajax/commission.php` et `classes/Rest.php` ont été supprimés : les données de
 * commission passent par `rest/action.php/commission/*`.
 */

test.describe('Issue #270 — endpoint orphelin supprimé', () => {

    test('/ajax/commission.php n\'est plus servi', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            const res = await ctx.get('/ajax/commission.php');
            expect(res.status(), "l'endpoint doit avoir disparu").toBe(404);
        } finally {
            await ctx.dispose();
        }
    });

    test('la sonde d\'injection ne renvoie plus de SQL', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            const res = await ctx.get("/ajax/commission.php?query='");
            expect(res.status()).toBe(404);

            // Le corps ne doit surtout plus contenir la requête SQL.
            const body = await res.text();
            expect(body).not.toContain('SELECT SQL_CALC_FOUND_ROWS');
            expect(body).not.toContain('FROM commission');
        } finally {
            await ctx.dispose();
        }
    });

    test('les données de commission restent servies par l\'API REST', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            // Le remplaçant légitime, utilisé par le front public et par l'admin.
            const res = await ctx.get('/rest/action.php/commission/get');
            expect(res.status()).toBe(200);
            expect(Array.isArray(await res.json())).toBe(true);
        } finally {
            await ctx.dispose();
        }
    });
});
