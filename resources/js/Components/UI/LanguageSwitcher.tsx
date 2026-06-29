import { useState } from 'react';
import { Menu, UnstyledButton, Group } from '@mantine/core';
import { IconChevronDown } from '@tabler/icons-react';
import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { setLocale, SUPPORTED_LOCALES, type SupportedLocale } from '@/lib/i18n';
import classes from './LanguageSwitcher.module.css';

const FLAGS: Record<SupportedLocale, string> = {
    en: '🇬🇧',
    pt: '🇵🇹',
};

const FULL: Record<SupportedLocale, 'english' | 'portuguese'> = {
    en: 'english',
    pt: 'portuguese',
};

type LanguageSwitcherProps = {
    compact?: boolean;
};

export default function LanguageSwitcher({ compact = false }: LanguageSwitcherProps) {
    const { t, i18n } = useTranslation('common');
    const [opened, setOpened] = useState(false);
    const current = (SUPPORTED_LOCALES as readonly string[]).includes(i18n.language)
        ? (i18n.language as SupportedLocale)
        : 'en';

    const handleSelect = async (loc: SupportedLocale) => {
        if (loc === current) return;
        await setLocale(loc);
        router.reload();
    };

    return (
        <Menu
            shadow="md"
            position="bottom-end"
            radius="md"
            width={compact ? 180 : 'target'}
            withinPortal
            onOpen={() => setOpened(true)}
            onClose={() => setOpened(false)}
        >
            <Menu.Target>
                <UnstyledButton
                    className={`${classes.control} ${compact ? classes.compact : ''}`}
                    data-expanded={opened || undefined}
                    aria-label={t('language.label')}
                >
                    <Group gap="xs" wrap="nowrap">
                        <span className={classes.flag}>{FLAGS[current]}</span>
                        {!compact && <span className={classes.label}>{t(`language.${FULL[current]}`)}</span>}
                    </Group>
                    {!compact && <IconChevronDown size={16} className={classes.icon} stroke={1.5} />}
                </UnstyledButton>
            </Menu.Target>
            <Menu.Dropdown>
                {SUPPORTED_LOCALES.map((loc) => (
                    <Menu.Item
                        key={loc}
                        leftSection={<span style={{ fontSize: 16 }}>{FLAGS[loc]}</span>}
                        onClick={() => handleSelect(loc)}
                    >
                        {t(`language.${FULL[loc]}`)}
                    </Menu.Item>
                ))}
            </Menu.Dropdown>
        </Menu>
    );
}
