import { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Badge,
    Box,
    Button,
    Card,
    Center,
    Container,
    Group,
    Loader,
    SegmentedControl,
    SimpleGrid,
    Stack,
    Tabs,
    Text,
    ThemeIcon,
    Title,
    Tooltip,
} from '@mantine/core';
import {
    IconAlertTriangle,
    IconArrowRight,
    IconCloudRain,
    IconDroplet,
    IconFlame,
    IconMap2,
    IconShieldCheck,
    IconTestPipe,
    IconWaveSine,
    IconRipple,
} from '@tabler/icons-react';
import { useTranslation } from 'react-i18next';
import { lazy, Suspense } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import {
    AquiferTrendChart,
    DamDrawdownChart,
    FlowDailyDischargeChart,
    RainfallHydrographChart,
    WqHistoricalChart,
} from '@/Components/Gis/HistoricalCharts';
import type { GisStationData } from '@/Components/Dashboard/GisMap';
import classes from './Explore.module.css';

const GisMap = lazy(() => import('@/Components/Dashboard/GisMap'));

type ModuleKey = 'dam_level' | 'flow' | 'rainfall' | 'groundwater_level' | 'water_quality';

type Confidence = 'verified' | 'provisional' | 'unknown';

type ExploreStation = GisStationData & {
    confidence: Confidence;
    is_alert: boolean;
    date: string | null;
};

type ModulePayload = {
    stations: ExploreStation[];
    historical: { date: string; value: number; samples: number }[];
    stats: {
        count: number;
        with_data: number;
        alerts: number;
        provisional: number;
        verified: number;
        last_updated: string | null;
    };
};

type HazardArea = {
    area_id: string;
    area_name: string;
    country: string | null;
    basin: string | null;
    hazard_code: string;
    hazard_name: string | null;
    level_code: string;
    level_name: string;
    color: string;
    severity: number;
    calculated_at: string | null;
    active_incidents: number;
};

type ExplorePageProps = {
    modules: Record<ModuleKey, ModulePayload>;
    hazardAreas: HazardArea[];
    stats: {
        total_stations: number;
        countries: number;
        last_sync: string | null;
        active_hazards: number;
        watch_or_above_areas: number;
    };
};

type ConfidenceFilter = 'all' | 'verified' | 'provisional';

const MODULE_ORDER: ModuleKey[] = ['dam_level', 'flow', 'rainfall', 'groundwater_level', 'water_quality'];

const MODULE_META: Record<ModuleKey, { color: string; icon: typeof IconDroplet }> = {
    dam_level: { color: 'cyan', icon: IconDroplet },
    flow: { color: 'blue', icon: IconWaveSine },
    rainfall: { color: 'indigo', icon: IconCloudRain },
    groundwater_level: { color: 'teal', icon: IconRipple },
    water_quality: { color: 'grape', icon: IconTestPipe },
};

function formatDateTime(value: string | null, locale: string): string {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleString(locale, { dateStyle: 'medium', timeStyle: 'short' });
}

function formatDate(value: string | null, locale: string): string {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString(locale, { dateStyle: 'medium' });
}

function applyConfidenceFilter(stations: ExploreStation[], filter: ConfidenceFilter): ExploreStation[] {
    if (filter === 'all') return stations;
    return stations.filter((s) => s.confidence === filter);
}

function ConfidenceLegend() {
    const { t } = useTranslation('explore');
    return (
        <Group gap="md" wrap="wrap" align="center">
            <Text size="xs" fw={600} c="dimmed" tt="uppercase">
                {t('confidence.label')}
            </Text>
            <Tooltip label={t('confidence.verifiedHelp')} withArrow position="bottom">
                <Group gap={6} wrap="nowrap">
                    <span className={classes.confidenceSwatch} style={{ backgroundColor: '#2b8a3e' }} />
                    <Text size="sm">{t('confidence.verified')}</Text>
                </Group>
            </Tooltip>
            <Tooltip label={t('confidence.provisionalHelp')} withArrow position="bottom">
                <Group gap={6} wrap="nowrap">
                    <span className={classes.confidenceSwatch} style={{ backgroundColor: '#e8590c' }} />
                    <Text size="sm">{t('confidence.provisional')}</Text>
                </Group>
            </Tooltip>
            <Tooltip label={t('confidence.unknownHelp')} withArrow position="bottom">
                <Group gap={6} wrap="nowrap">
                    <span className={classes.confidenceSwatch} style={{ backgroundColor: '#868e96' }} />
                    <Text size="sm">{t('confidence.unknown')}</Text>
                </Group>
            </Tooltip>
        </Group>
    );
}

function MapSkeleton({ height }: { height: number }) {
    return (
        <Center style={{ height }}>
            <Loader size="sm" />
        </Center>
    );
}

function ModuleHistoricalChart({ moduleKey, data }: { moduleKey: ModuleKey | 'overview'; data: { date: string; value: number }[] }) {
    if (moduleKey === 'overview') return null;
    if (data.length === 0) {
        return (
            <Center style={{ height: 260 }}>
                <Text size="sm" c="dimmed">No historical data yet.</Text>
            </Center>
        );
    }
    switch (moduleKey) {
        case 'dam_level':
            return <DamDrawdownChart data={data} />;
        case 'flow':
            return <FlowDailyDischargeChart data={data} />;
        case 'rainfall':
            return <RainfallHydrographChart data={data} />;
        case 'groundwater_level':
            return <AquiferTrendChart readings={data} />;
        case 'water_quality':
            return <WqHistoricalChart data={data} parameter="Indicator" />;
        default:
            return null;
    }
}

export default function ExplorePage({ modules, hazardAreas, stats }: ExplorePageProps) {
    const { t, i18n } = useTranslation('explore');
    const [activeTab, setActiveTab] = useState<ModuleKey | 'overview' | 'hazards'>('overview');
    const [confidenceFilter, setConfidenceFilter] = useState<ConfidenceFilter>('all');

    const overviewStations = useMemo<ExploreStation[]>(() => {
        const map = new Map<string, ExploreStation>();
        MODULE_ORDER.forEach((key) => {
            modules[key]?.stations.forEach((s) => {
                if (!map.has(s.id)) {
                    map.set(s.id, s);
                }
            });
        });
        return Array.from(map.values());
    }, [modules]);

    const stationsForMap = useMemo<ExploreStation[]>(() => {
        if (activeTab === 'overview') return applyConfidenceFilter(overviewStations, confidenceFilter);
        if (activeTab === 'hazards') return [];
        return applyConfidenceFilter(modules[activeTab]?.stations ?? [], confidenceFilter);
    }, [activeTab, confidenceFilter, modules, overviewStations]);

    const legends = useMemo(
        () => [
            { color: '#2b8a3e', label: t('footer.verified') },
            { color: '#e8590c', label: t('footer.provisional') },
            { color: '#e03131', label: t('footer.alert') },
            { color: '#868e96', label: t('footer.noData') },
        ],
        [t],
    );

    const headlineKpi = [
        { label: t('kpi.stations'), value: stats.total_stations },
        { label: t('kpi.countries'), value: stats.countries },
        { label: t('kpi.watchAreas'), value: stats.watch_or_above_areas },
        { label: t('kpi.activeHazards'), value: stats.active_hazards },
    ];

    const locale = i18n.language;

    return (
        <>
            <Head title={t('head.title')}>
                <meta name="description" content={t('head.description')} />
            </Head>

            <div className={classes.page}>
                {/* HERO */}
                <section className={classes.hero}>
                    <Container size="xl">
                        <Stack gap="lg">
                            <Stack gap={6}>
                                <Text className={classes.eyebrow}>{t('hero.eyebrow')}</Text>
                                <Title className={classes.heroTitle} order={1}>
                                    {t('hero.title')}
                                </Title>
                                <Text className={classes.heroSubtitle}>{t('hero.subtitle')}</Text>
                            </Stack>

                            <Group justify="space-between" align="center" wrap="wrap">
                                <ConfidenceLegend />
                                <Badge variant="light" color="gray" size="lg">
                                    {stats.last_sync
                                        ? `${t('hero.lastSync')}: ${formatDateTime(stats.last_sync, locale)}`
                                        : t('hero.noSync')}
                                </Badge>
                            </Group>

                            <SimpleGrid cols={{ base: 2, sm: 4 }} spacing="md">
                                {headlineKpi.map((kpi) => (
                                    <div key={kpi.label} className={classes.kpiCard}>
                                        <Text className={classes.kpiLabel}>{kpi.label}</Text>
                                        <Text className={classes.kpiValue}>{kpi.value}</Text>
                                    </div>
                                ))}
                            </SimpleGrid>
                        </Stack>
                    </Container>
                </section>

                {/* MODULE TABS (sticky) */}
                <div className={classes.tabsBar}>
                    <Container size="xl">
                        <Tabs
                            value={activeTab}
                            onChange={(value) => setActiveTab((value as typeof activeTab) ?? 'overview')}
                            variant="pills"
                            color="blue"
                        >
                            <Tabs.List style={{ paddingTop: 12, paddingBottom: 12, flexWrap: 'wrap', gap: 8 }}>
                                <Tabs.Tab value="overview" leftSection={<IconMap2 size={16} />}>
                                    {t('tabs.overview')}
                                </Tabs.Tab>
                                {MODULE_ORDER.map((key) => {
                                    const Meta = MODULE_META[key];
                                    const Icon = Meta.icon;
                                    return (
                                        <Tabs.Tab key={key} value={key} leftSection={<Icon size={16} />}>
                                            {t(`tabs.${key}`)}
                                        </Tabs.Tab>
                                    );
                                })}
                                <Tabs.Tab value="hazards" color="red" leftSection={<IconAlertTriangle size={16} />}>
                                    {t('tabs.hazards')}
                                </Tabs.Tab>
                            </Tabs.List>
                        </Tabs>
                    </Container>
                </div>

                {/* MAP + FILTER */}
                <section className={classes.section}>
                    <Container size="xl">
                        <Stack gap="md">
                            <Group justify="space-between" align="flex-end" wrap="wrap" gap="md">
                                <Stack gap={2}>
                                    <Text className={classes.sectionTitle}>{t('map.title')}</Text>
                                    <Text className={classes.sectionSubtitle}>
                                        {activeTab === 'overview' || activeTab === 'hazards'
                                            ? t('map.subtitleOverview')
                                            : t('map.subtitleModule')}
                                    </Text>
                                </Stack>

                                {activeTab !== 'hazards' && (
                                    <SegmentedControl
                                        value={confidenceFilter}
                                        onChange={(v) => setConfidenceFilter(v as ConfidenceFilter)}
                                        data={[
                                            { label: t('confidence.filter.all'), value: 'all' },
                                            { label: t('confidence.filter.verified'), value: 'verified' },
                                            { label: t('confidence.filter.provisional'), value: 'provisional' },
                                        ]}
                                    />
                                )}
                            </Group>

                            <Card withBorder padding={0} radius="md" className={classes.fadeIn} key={`${activeTab}-${confidenceFilter}`}>
                                {activeTab === 'hazards' ? (
                                    <HazardAreaTable hazardAreas={hazardAreas} />
                                ) : (
                                    <Suspense fallback={<MapSkeleton height={560} />}>
                                        <GisMap
                                            stations={stationsForMap}
                                            legendTitle={t('map.title')}
                                            legends={legends}
                                            height={560}
                                            defaultLegendCollapsed
                                        />
                                    </Suspense>
                                )}
                            </Card>
                        </Stack>
                    </Container>
                </section>

                {/* MODULE-SPECIFIC HISTORICAL CHART */}
                {activeTab !== 'overview' && activeTab !== 'hazards' && (
                    <section className={classes.section} style={{ paddingTop: 0 }}>
                        <Container size="xl">
                            <Card withBorder radius="md" padding="lg" className={classes.fadeIn} key={`chart-${activeTab}`}>
                                <Stack gap="md">
                                    <Stack gap={2}>
                                        <Text className={classes.sectionTitle}>{t('module.historicalTitle')}</Text>
                                        <Text className={classes.sectionSubtitle}>{t('module.historicalDescription')}</Text>
                                    </Stack>
                                    <ModuleHistoricalChart moduleKey={activeTab} data={modules[activeTab]?.historical ?? []} />
                                </Stack>
                            </Card>
                        </Container>
                    </section>
                )}

                {/* MODULE INSIGHT CARDS */}
                <section className={classes.section} style={{ paddingTop: 0 }}>
                    <Container size="xl">
                        <Stack gap="md">
                            <Stack gap={2}>
                                <Text className={classes.sectionTitle}>{t('hero.eyebrow')}</Text>
                                <Text className={classes.sectionSubtitle}>{t('module.historicalDescription')}</Text>
                            </Stack>

                            <SimpleGrid cols={{ base: 1, sm: 2, lg: 3 }} spacing="md">
                                {MODULE_ORDER.map((key) => (
                                    <ModuleInsightCard
                                        key={key}
                                        moduleKey={key}
                                        payload={modules[key]}
                                        active={activeTab === key}
                                        onSelect={() => setActiveTab(key)}
                                        locale={locale}
                                    />
                                ))}
                                <HazardSummaryCard
                                    hazardAreas={hazardAreas}
                                    active={activeTab === 'hazards'}
                                    onSelect={() => setActiveTab('hazards')}
                                />
                            </SimpleGrid>
                        </Stack>
                    </Container>
                </section>

                {/* HAZARDS PANEL */}
                <section className={classes.section} style={{ paddingTop: 0 }}>
                    <Container size="xl">
                        <Card withBorder radius="md" padding={0}>
                            <Box p="lg" style={{ borderBottom: '1px solid var(--mantine-color-gray-2)' }}>
                                <Stack gap={2}>
                                    <Group gap="xs">
                                        <ThemeIcon color="red" variant="light" radius="md">
                                            <IconAlertTriangle size={18} />
                                        </ThemeIcon>
                                        <Text className={classes.sectionTitle}>{t('hazards.title')}</Text>
                                    </Group>
                                    <Text className={classes.sectionSubtitle}>{t('hazards.subtitle')}</Text>
                                </Stack>
                            </Box>
                            <HazardAreaTable hazardAreas={hazardAreas} />
                        </Card>

                        <Group justify="center" mt="xl">
                            <Text size="xs" c="dimmed">
                                {t('footer.providedBy')}
                            </Text>
                        </Group>
                    </Container>
                </section>
            </div>
        </>
    );
}

function ModuleInsightCard({
    moduleKey,
    payload,
    active,
    onSelect,
    locale,
}: {
    moduleKey: ModuleKey;
    payload?: ModulePayload;
    active: boolean;
    onSelect: () => void;
    locale: string;
}) {
    const { t } = useTranslation('explore');
    const Meta = MODULE_META[moduleKey];
    const Icon = Meta.icon;
    const stats = payload?.stats ?? { count: 0, with_data: 0, alerts: 0, provisional: 0, verified: 0, last_updated: null };

    return (
        <button
            type="button"
            onClick={onSelect}
            className={`${classes.moduleCard} ${active ? classes.moduleCardActive : ''}`}
            style={{ textAlign: 'left', font: 'inherit', cursor: 'pointer', border: undefined }}
        >
            <Group justify="space-between" wrap="nowrap">
                <Group gap="sm" wrap="nowrap">
                    <ThemeIcon color={Meta.color} variant="light" radius="md" size={36}>
                        <Icon size={20} />
                    </ThemeIcon>
                    <Stack gap={0}>
                        <Text fw={700} size="sm">{t(`tabs.${moduleKey}`)}</Text>
                        <Text size="xs" c="dimmed">
                            {stats.last_updated
                                ? `${t('module.lastReading')}: ${formatDate(stats.last_updated, locale)}`
                                : t('module.noReadings')}
                        </Text>
                    </Stack>
                </Group>
                <ThemeIcon color="gray" variant="subtle" radius="xl" size={28}>
                    <IconArrowRight size={16} />
                </ThemeIcon>
            </Group>

            <SimpleGrid cols={3} spacing="xs" mt="sm">
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('module.stations')}</Text>
                    <Text className={classes.miniStatValue}>{stats.count}</Text>
                </div>
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('module.verified')}</Text>
                    <Text className={classes.miniStatValue} c="teal.7">{stats.verified}</Text>
                </div>
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('module.provisional')}</Text>
                    <Text className={classes.miniStatValue} c="orange.7">{stats.provisional}</Text>
                </div>
            </SimpleGrid>

            {stats.alerts > 0 && (
                <Badge color="red" variant="light" leftSection={<IconFlame size={12} />}>
                    {stats.alerts} {t('module.alerts')}
                </Badge>
            )}
        </button>
    );
}

function HazardSummaryCard({
    hazardAreas,
    active,
    onSelect,
}: {
    hazardAreas: HazardArea[];
    active: boolean;
    onSelect: () => void;
}) {
    const { t } = useTranslation('explore');
    const watch = hazardAreas.filter((h) => h.severity >= 2).length;
    const highest = hazardAreas[0];

    return (
        <button
            type="button"
            onClick={onSelect}
            className={`${classes.moduleCard} ${active ? classes.moduleCardActive : ''}`}
            style={{ textAlign: 'left', font: 'inherit', cursor: 'pointer', border: undefined }}
        >
            <Group justify="space-between" wrap="nowrap">
                <Group gap="sm" wrap="nowrap">
                    <ThemeIcon color="red" variant="light" radius="md" size={36}>
                        <IconAlertTriangle size={20} />
                    </ThemeIcon>
                    <Stack gap={0}>
                        <Text fw={700} size="sm">{t('tabs.hazards')}</Text>
                        <Text size="xs" c="dimmed">
                            {highest ? `${highest.area_name} · ${highest.level_name}` : t('hazards.empty')}
                        </Text>
                    </Stack>
                </Group>
                <ThemeIcon color="gray" variant="subtle" radius="xl" size={28}>
                    <IconArrowRight size={16} />
                </ThemeIcon>
            </Group>

            <SimpleGrid cols={3} spacing="xs" mt="sm">
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('kpi.watchAreas')}</Text>
                    <Text className={classes.miniStatValue} c="red.7">{watch}</Text>
                </div>
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('kpi.activeHazards')}</Text>
                    <Text className={classes.miniStatValue}>{hazardAreas.reduce((sum, h) => sum + h.active_incidents, 0)}</Text>
                </div>
                <div className={classes.miniStat}>
                    <Text className={classes.miniStatLabel}>{t('hazards.area')}</Text>
                    <Text className={classes.miniStatValue}>{hazardAreas.length}</Text>
                </div>
            </SimpleGrid>
        </button>
    );
}

function HazardAreaTable({ hazardAreas }: { hazardAreas: HazardArea[] }) {
    const { t, i18n } = useTranslation('explore');
    if (hazardAreas.length === 0) {
        return (
            <Center p="xl">
                <Group gap="sm">
                    <ThemeIcon color="green" variant="light" radius="md">
                        <IconShieldCheck size={18} />
                    </ThemeIcon>
                    <Text c="dimmed">{t('hazards.empty')}</Text>
                </Group>
            </Center>
        );
    }
    return (
        <div>
            <div className={classes.hazardRow} style={{ background: 'var(--mantine-color-gray-0)', fontWeight: 600 }}>
                <Text size="xs" tt="uppercase" c="dimmed">{t('hazards.area')}</Text>
                <Text size="xs" tt="uppercase" c="dimmed">{t('hazards.hazard')}</Text>
                <Text size="xs" tt="uppercase" c="dimmed">{t('hazards.level')}</Text>
                <Text size="xs" tt="uppercase" c="dimmed">{t('hazards.activeIncidents')}</Text>
            </div>
            {hazardAreas.map((h) => (
                <div key={`${h.area_id}-${h.hazard_code}`} className={classes.hazardRow}>
                    <Stack gap={0}>
                        <Text fw={600} size="sm">{h.area_name}</Text>
                        <Text size="xs" c="dimmed">
                            {[h.country, h.basin].filter(Boolean).join(' · ')}
                        </Text>
                    </Stack>
                    <Text size="sm">{h.hazard_name ?? h.hazard_code}</Text>
                    <Group gap={6} wrap="nowrap">
                        <span className={classes.hazardSeverityDot} style={{ backgroundColor: h.color }} />
                        <Text size="sm" fw={500}>{h.level_name}</Text>
                    </Group>
                    <Group justify="space-between" wrap="nowrap">
                        <Badge variant="light" color={h.active_incidents > 0 ? 'red' : 'gray'}>
                            {h.active_incidents}
                        </Badge>
                        <Text size="xs" c="dimmed">{formatDate(h.calculated_at, i18n.language)}</Text>
                    </Group>
                </div>
            ))}
        </div>
    );
}

ExplorePage.layout = (page: React.ReactNode) => <PublicLayout>{page}</PublicLayout>;
