import '../css/app.css';
import './bootstrap';
import '@mantine/core/styles.css';
import '@mantine/dropzone/styles.css';
import '@mantine/notifications/styles.css';
import 'leaflet/dist/leaflet.css';
import '@/lib/i18n';

import { createInertiaApp } from '@inertiajs/react';
import {
    Button,
    MantineProvider,
    Modal,
    NumberInput,
    PasswordInput,
    Select,
    Textarea,
    TextInput,
    createTheme,
    localStorageColorSchemeManager,
} from '@mantine/core';
import { Notifications } from '@mantine/notifications';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const colorSchemeManager = localStorageColorSchemeManager({ key: 'inmacom-color-scheme' });

const theme = createTheme({
    primaryColor: 'dark',
    fontFamily:
        'Inter, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
    components: {
        TextInput: TextInput.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
        PasswordInput: PasswordInput.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
        NumberInput: NumberInput.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
        Select: Select.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
        Textarea: Textarea.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
        Modal: Modal.extend({
            defaultProps: { radius: 'md' },
        }),
        Button: Button.extend({
            defaultProps: { radius: 'md', size: 'md' },
        }),
    },
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <MantineProvider
                theme={theme}
                defaultColorScheme="auto"
                colorSchemeManager={colorSchemeManager}
            >
                <Notifications position="top-right" />
                <App {...props} />
            </MantineProvider>,
        );
    },
    progress: {
        color: 'var(--mantine-primary-color-filled)',
    },
});
