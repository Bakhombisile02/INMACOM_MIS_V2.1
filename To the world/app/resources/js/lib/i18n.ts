import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

import enCommon from '@/locales/en/common.json';
import ptCommon from '@/locales/pt/common.json';
import enLanding from '@/locales/en/landing.json';
import ptLanding from '@/locales/pt/landing.json';
import enAuth from '@/locales/en/auth.json';
import ptAuth from '@/locales/pt/auth.json';
import enApp from '@/locales/en/app.json';
import ptApp from '@/locales/pt/app.json';
import enDocuments from '@/locales/en/documents.json';
import ptDocuments from '@/locales/pt/documents.json';
import enNavigation from '@/locales/en/navigation.json';
import ptNavigation from '@/locales/pt/navigation.json';
import enStations from '@/locales/en/stations.json';
import ptStations from '@/locales/pt/stations.json';
import enErrors from '@/locales/en/errors.json';
import ptErrors from '@/locales/pt/errors.json';
import enUsers from '@/locales/en/users.json';
import ptUsers from '@/locales/pt/users.json';
import enSettings from '@/locales/en/settings.json';
import ptSettings from '@/locales/pt/settings.json';
import enThresholds from '@/locales/en/thresholds.json';
import ptThresholds from '@/locales/pt/thresholds.json';
import enDisaster from '@/locales/en/disaster.json';
import ptDisaster from '@/locales/pt/disaster.json';
import enGis from '@/locales/en/gis.json';
import ptGis from '@/locales/pt/gis.json';
import enApprovals from '@/locales/en/approvals.json';
import ptApprovals from '@/locales/pt/approvals.json';
import enAudit from '@/locales/en/audit.json';
import ptAudit from '@/locales/pt/audit.json';
import enExplore from '@/locales/en/explore.json';
import ptExplore from '@/locales/pt/explore.json';

export const SUPPORTED_LOCALES = ['en', 'pt'] as const;
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];
export const LOCALE_COOKIE = 'INMACOM_LOCALE';

void i18n
    .use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources: {
            en: {
                common: enCommon,
                landing: enLanding,
                auth: enAuth,
                app: enApp,
                documents: enDocuments,
                navigation: enNavigation,
                stations: enStations,
                errors: enErrors,
                users: enUsers,
                settings: enSettings,
                thresholds: enThresholds,
                disaster: enDisaster,
                gis: enGis,
                approvals: enApprovals,
                audit: enAudit,
                explore: enExplore,
            },
            pt: {
                common: ptCommon,
                landing: ptLanding,
                auth: ptAuth,
                app: ptApp,
                documents: ptDocuments,
                navigation: ptNavigation,
                stations: ptStations,
                errors: ptErrors,
                users: ptUsers,
                settings: ptSettings,
                thresholds: ptThresholds,
                disaster: ptDisaster,
                gis: ptGis,
                approvals: ptApprovals,
                audit: ptAudit,
                explore: ptExplore,
            },
        },
        fallbackLng: 'en',
        supportedLngs: SUPPORTED_LOCALES as unknown as string[],
        ns: ['common', 'landing', 'auth', 'app', 'documents', 'navigation', 'stations', 'errors', 'users', 'settings', 'thresholds', 'disaster', 'gis', 'approvals', 'audit', 'explore'],
        defaultNS: 'common',
        interpolation: { escapeValue: false },
        detection: {
            order: ['cookie', 'htmlTag', 'navigator'],
            caches: ['cookie'],
            lookupCookie: LOCALE_COOKIE,
            cookieMinutes: 60 * 24 * 365,
        },
        react: { useSuspense: false },
    });

export function setLocale(locale: SupportedLocale) {
    document.cookie = `${LOCALE_COOKIE}=${locale}; path=/; max-age=${60 * 60 * 24 * 365}; SameSite=Lax`;
    return i18n.changeLanguage(locale);
}

export default i18n;
