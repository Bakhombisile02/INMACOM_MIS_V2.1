import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';

// Recharts 3.x imports es-toolkit/compat/* as individual CJS shim files that
// have no default export in Vite dev mode. These aliases redirect each sub-path
// to a thin ESM shim that re-exports the named function as default from the
// proper es-toolkit/compat ESM entry, fixing the dev-mode runtime crash.
const SHIMS = path.resolve(__dirname, 'resources/js/shims/es-toolkit');
const esToolkitCompatAliases = [
    'get', 'isPlainObject', 'last', 'maxBy', 'minBy',
    'omit', 'range', 'sortBy', 'sumBy', 'throttle', 'uniqBy',
].map(fn => ({
    find: `es-toolkit/compat/${fn}`,
    replacement: path.join(SHIMS, `${fn}.js`),
}));

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: esToolkitCompatAliases,
    },
});
