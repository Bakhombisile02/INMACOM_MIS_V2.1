import { useEffect, useState, useRef, Fragment, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import StationEditDrawer from './StationEditDrawer';
import type { StationMapMarker } from './MultiStationMap';
import L from 'leaflet';
import { ActionIcon, Card, Tooltip as MantineTooltip, Stack, Text, Switch, Group, Divider, Collapse, SegmentedControl } from '@mantine/core';
import { IconMap, IconSatellite, IconLayersIntersect, IconChevronDown, IconChevronUp, IconHexagons, IconFlame, IconEye, IconMaximize, IconMinimize } from '@tabler/icons-react';
import { GeoJSON, MapContainer, TileLayer, CircleMarker, Marker, Popup, Tooltip as LeafletTooltip, useMap, useMapEvents, ScaleControl } from 'react-leaflet';
import { incomati, maputo } from '@/assets/basinBoundaries';
import { generateHexBins } from '@/lib/HexGrid';

export interface GisStationData {
    id: string;
    code: string;
    name: string;
    latitude: number;
    longitude: number;
    country?: string | null;
    river_basin?: string | null;
    is_real_time: boolean;
    owner_org?: string | null;
    value: number | null;
    unit: string;
    color: string;
    radius?: number;
    popupData: { label: string; value: string; color?: string }[];
    // Station info fields (populated by GIS pages for the info/edit drawers)
    show_url?: string | null;
    status?: 'active' | 'inactive' | null;
    is_active?: boolean | null;
    category?: string | null;
    water_source?: string | null;
    water_body_type?: string | null;
    summary?: string | null;
    telemetry_system?: string | null;
    gauge_code?: string | null;
}

const OSM_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const SAT_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
const SAT_ATTR = 'Tiles &copy; <a href="https://www.esri.com">Esri Imagery</a>';
const CANVAS_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';
const CANVAS_ATTR = 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ';

const DEFAULT_CENTER: [number, number] = [-25.7, 31.8];

// Auto-zooms to include all loaded stations
function BoundsUpdater({ stations }: { stations: GisStationData[] }) {
    const map = useMap();
    const hasRun = useRef(false);

    useEffect(() => {
        if (hasRun.current) return;
        const coords = stations.map((s) => [s.latitude, s.longitude] as [number, number]);
        if (coords.length === 0) return;
        hasRun.current = true;
        if (coords.length === 1) {
            map.setView(coords[0], 14);
            return;
        }
        map.fitBounds(L.latLngBounds(coords), { padding: [50, 50], maxZoom: 11 });
    }, [map, stations]);

    return null;
}

// Calls map.invalidateSize() when fullscreen state changes so Leaflet recalculates its event/tile layer
function FullscreenResizer({ isFullscreen }: { isFullscreen: boolean }) {
    const map = useMap();
    useEffect(() => {
        const timer = setTimeout(() => map.invalidateSize({ animate: false }), 150);
        return () => clearTimeout(timer);
    }, [isFullscreen, map]);
    return null;
}

// Updates the coordinate pill DOM node directly — no React state, no re-renders
function CoordDisplay({ divRef }: { divRef: React.RefObject<HTMLDivElement | null> }) {
    useMapEvents({
        mousemove(e) {
            if (divRef.current) {
                divRef.current.textContent = `${e.latlng.lat.toFixed(4)}°, ${e.latlng.lng.toFixed(4)}°`;
                divRef.current.style.display = 'block';
            }
        },
        mouseout() {
            if (divRef.current) divRef.current.style.display = 'none';
        },
    });
    return null;
}

// Subcomponent to handle cluster group calculations and map events (Esri Cluster Map Technique)
interface ClusteredMarkersProps {
    stations: GisStationData[];
    getMarkerRadius: (station: GisStationData) => number;
    onMarkerClick?: (id: string) => void;
}

function ClusteredMarkers({ stations, getMarkerRadius, onMarkerClick }: ClusteredMarkersProps) {
    const map = useMap();
    const [, forceUpdate] = useState({});

    useEffect(() => {
        const handleEvent = () => forceUpdate({});
        map.on('zoomend', handleEvent);
        map.on('moveend', handleEvent);
        return () => {
            map.off('zoomend', handleEvent);
            map.off('moveend', handleEvent);
        };
    }, [map]);

    const zoom = map.getZoom();

    // Grid-based clustering logic (cell size decreases as zoom level increases)
    const gridCellSize = Math.max(0.012, 360 / Math.pow(2, zoom) * 0.35);

    // Group stations into grid cells
    const clusters: { [key: string]: GisStationData[] } = {};

    stations.forEach((station) => {
        const gridX = Math.round(station.longitude / gridCellSize);
        const gridY = Math.round(station.latitude / gridCellSize);
        const key = `${gridX}_${gridY}`;
        if (!clusters[key]) {
            clusters[key] = [];
        }
        clusters[key].push(station);
    });

    return (
        <>
            {Object.entries(clusters).map(([clusterKey, group]) => {
                if (group.length === 1) {
                    const station = group[0];
                    const isAlert = station.color === 'rgb(255, 0, 0)';
                    const isNoData = station.color === 'rgb(127, 127, 127)';

                    return (
                        <Fragment key={station.id}>
                            {/* Pulse ring for non-compliant/alert stations (accessibility + salience) */}
                            {isAlert && (
                                <CircleMarker
                                    center={[station.latitude, station.longitude]}
                                    radius={getMarkerRadius(station) + 8}
                                    pathOptions={{
                                        fillColor: '#e03131',
                                        color: 'transparent',
                                        fillOpacity: 0.28,
                                        weight: 0,
                                        className: 'gis-alert-pulse',
                                    }}
                                />
                            )}
                            <CircleMarker
                                center={[station.latitude, station.longitude]}
                                radius={getMarkerRadius(station)}
                                pathOptions={{
                                    fillColor: station.color,
                                    color: isAlert ? '#c92a2a' : isNoData ? '#495057' : '#106ba3',
                                    weight: isAlert ? 2.8 : 1.5,
                                    dashArray: isAlert ? '4, 4' : isNoData ? '1, 4' : undefined,
                                    opacity: 1.0,
                                    fillOpacity: 0.85,
                                }}
                                eventHandlers={onMarkerClick ? { click: () => onMarkerClick(station.id) } : undefined}
                            >
                            <LeafletTooltip sticky direction="top">
                                <span style={{ fontFamily: 'monospace', fontWeight: 600 }}>({station.code})</span> {station.name}: {station.value !== null ? `${station.value} ${station.unit}` : 'No Data'}
                            </LeafletTooltip>
                            {!onMarkerClick && (
                                <Popup>
                                    <Card p="xs" radius="md" style={{ minWidth: 200 }}>
                                        <Text fw={700} size="sm" c="blue" ta="center" mb="xs" ff="monospace">
                                            {station.code}
                                        </Text>
                                        <Stack gap={4}>
                                            <Text size="xs"><strong>Station:</strong> {station.name}</Text>
                                            <Text size="xs"><strong>Basin:</strong> {station.river_basin ?? '—'}</Text>
                                            {station.popupData.map((row, idx) => (
                                                <Text key={idx} size="xs">
                                                    <strong>{row.label}:</strong>{' '}
                                                    <span style={{ color: row.color, fontWeight: row.color ? 600 : 'normal' }}>
                                                        {row.value}
                                                    </span>
                                                </Text>
                                            ))}
                                            <Text size="xs" c="dimmed" mt={4}>
                                                Lat: {station.latitude.toFixed(4)}, Lng: {station.longitude.toFixed(4)}
                                            </Text>
                                        </Stack>
                                    </Card>
                                </Popup>
                            )}
                            </CircleMarker>
                        </Fragment>
                    );
                }

                // Cluster marker representation (Esri visual styling with high contrast rings)
                const count = group.length;
                const avgLat = group.reduce((sum, s) => sum + s.latitude, 0) / count;
                const avgLng = group.reduce((sum, s) => sum + s.longitude, 0) / count;

                // Detect if all points have identical coordinates (overlapping points)
                const first = group[0];
                const allSameCoord = group.every(s => Math.abs(s.latitude - first.latitude) < 0.0001 && Math.abs(s.longitude - first.longitude) < 0.0001);

                // Detect predominance/alerts in the cluster (Esri summary methodology)
                const alertCount = group.filter(s => s.color === 'rgb(255, 0, 0)' || s.color === '#ff0000').length;
                const noDataCount = group.filter(s => s.color === 'rgb(127, 127, 127)' || s.color === '#7f7f7f').length;
                const isAlertCluster = alertCount > 0;
                const isNoDataCluster = noDataCount === count;

                // Color themes matching the theme palette
                let colorBase = '16, 107, 163'; // Default primary blue
                if (isAlertCluster) {
                    colorBase = '224, 49, 49'; // Vivid Alert Red
                } else if (isNoDataCluster) {
                    colorBase = '108, 117, 125'; // Muted dark-gray for no data
                }

                // Base size proportional to count
                const baseSize = 28 + Math.min(count * 1.5, 12);
                const outerSize = baseSize + 16;
                const middleSize = baseSize + 8;
                const coreSize = baseSize;

                // Concentric circles: pulsing outer ring, translucent middle ring, solid high contrast core
                const iconHtml = `<div style="
                    position: relative;
                    width: ${outerSize}px;
                    height: ${outerSize}px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <!-- Outer Ring / Pulsing Halo -->
                    <div style="
                        position: absolute;
                        width: ${outerSize}px;
                        height: ${outerSize}px;
                        border-radius: 50%;
                        background-color: rgba(${colorBase}, 0.15);
                        ${isAlertCluster ? 'animation: cluster-pulse 1.8s infinite ease-in-out;' : ''}
                    "></div>
                    <!-- Middle Ring -->
                    <div style="
                        position: absolute;
                        width: ${middleSize}px;
                        height: ${middleSize}px;
                        border-radius: 50%;
                        background-color: rgba(${colorBase}, 0.35);
                    "></div>
                    <!-- Core Ring -->
                    <div style="
                        position: absolute;
                        width: ${coreSize}px;
                        height: ${coreSize}px;
                        border-radius: 50%;
                        background-color: rgb(${colorBase});
                        border: 2px solid #ffffff;
                        color: #ffffff;
                        font-weight: 800;
                        font-size: 11px;
                        font-family: monospace;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 3px 6px rgba(0,0,0,0.16);
                        cursor: pointer;
                    ">
                        ${count}
                    </div>
                </div>`;

                const customIcon = L.divIcon({
                    html: iconHtml,
                    className: 'custom-cluster-marker',
                    iconSize: [outerSize, outerSize],
                    iconAnchor: [outerSize / 2, outerSize / 2],
                });

                const shouldShowPopup = allSameCoord || zoom >= 14;

                return (
                    <Marker
                        key={clusterKey}
                        position={[avgLat, avgLng]}
                        icon={customIcon}
                        eventHandlers={{
                            click: (e) => {
                                if (!shouldShowPopup) {
                                    L.DomEvent.stopPropagation(e);
                                    const coords = group.map((s) => [s.latitude, s.longitude] as [number, number]);
                                    map.fitBounds(L.latLngBounds(coords), { padding: [40, 40], maxZoom: zoom + 2 });
                                }
                            },
                        }}
                    >
                        {!shouldShowPopup && (
                            <LeafletTooltip sticky direction="top">
                                <strong>Cluster ({count} Stations)</strong><br />
                                {isAlertCluster && <span style={{ color: '#ff8787', fontWeight: 600 }}>Alert Present inside Cluster<br /></span>}
                                Click to zoom in and expand cluster
                            </LeafletTooltip>
                        )}
                        {shouldShowPopup && (
                            <Popup>
                                <Card p="xs" radius="md" style={{ minWidth: 220, maxWidth: 280, border: 'none', boxShadow: 'none' }}>
                                    <Text fw={700} size="xs" c={isAlertCluster ? 'red.7' : 'blue.7'} ta="center" mb="xs" style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>
                                        Grouped Stations ({count})
                                    </Text>
                                    <Stack gap={6} style={{ maxHeight: 200, overflowY: 'auto' }}>
                                        {group.map((station) => (
                                            <div key={station.id} style={{ borderBottom: '1px solid #e9ecef', paddingBottom: 6, paddingTop: 4 }}>
                                                <Group justify="space-between" wrap="nowrap" gap={8}>
                                                    <Text size="xs" fw={700} style={{ fontFamily: 'monospace' }}>{station.code}</Text>
                                                    <span style={{ 
                                                        width: 8, 
                                                        height: 8, 
                                                        borderRadius: '50%', 
                                                        backgroundColor: station.color,
                                                        border: station.color === 'rgb(255, 0, 0)' ? '1px solid #c92a2a' : 'none'
                                                    }} />
                                                </Group>
                                                <Text size="11px" fw={500} lineClamp={1}>{station.name}</Text>
                                                <Text size="10px" c="dimmed">
                                                    Value: <span style={{ color: station.color, fontWeight: 600 }}>
                                                        {station.value !== null ? `${station.value} ${station.unit}` : 'No Data'}
                                                    </span>
                                                </Text>
                                            </div>
                                        ))}
                                    </Stack>
                                </Card>
                            </Popup>
                        )}
                    </Marker>
                );
            })}
        </>
    );
}

interface LegendItem {
    color: string;
    label: string;
}

interface GisMapProps {
    stations: GisStationData[];
    legendTitle: string;
    legends: LegendItem[];
    height?: number;
    selectedId?: string | null;
    onMarkerClick?: (id: string) => void;
    onDeselect?: () => void;
    canManage?: boolean;
    onFullscreenChange?: (v: boolean) => void;
    /** When provided, rendered inside the map container in fullscreen mode instead of StationEditDrawer */
    renderFullscreenDrawer?: (selectedId: string | null, onClose: () => void) => ReactNode;
    /** Start with the legend panel collapsed */
    defaultLegendCollapsed?: boolean;
}

export default function GisMap({
    stations,
    legendTitle,
    legends,
    height = 540,
    selectedId,
    onMarkerClick,
    onDeselect,
    canManage = false,
    onFullscreenChange,
    renderFullscreenDrawer,
    defaultLegendCollapsed = false,
}: GisMapProps) {
    // Default to 'muted' Light Gray Canvas for high accessibility (Esri guidelines)
    const [baseMap, setBaseMap] = useState<'osm' | 'muted' | 'satellite'>('muted');
    const [showBasins, setShowBasins] = useState(true);
    const [showStations, setShowStations] = useState(true);
    const [controlsExpanded, setControlsExpanded] = useState(false);
    const [legendCollapsed, setLegendCollapsed] = useState(defaultLegendCollapsed);
    const [visMode, setVisMode] = useState<'badges' | 'hex' | 'hotspots'>('badges');
    const [isFullscreen, setIsFullscreen] = useState(false);
    const mapContainerRef = useRef<HTMLDivElement>(null);
    const coordDisplayRef = useRef<HTMLDivElement>(null);
    const { t } = useTranslation('gis');

    // Use native Fullscreen API — avoids stacking context issues with parent layout
    useEffect(() => {
        const handler = () => {
            const fs = !!document.fullscreenElement;
            setIsFullscreen(fs);
            onFullscreenChange?.(fs);
            setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
        };
        document.addEventListener('fullscreenchange', handler);
        return () => document.removeEventListener('fullscreenchange', handler);
    }, [onFullscreenChange]);

    // Count alert stations for legend header badge
    const alertCount = stations.filter(s => s.color === 'rgb(255, 0, 0)' || s.color === '#ff0000').length;

    // Generate hexagonal grid bins with high fidelity (smaller spacing for detailed spatial grid)
    const hexBins = generateHexBins(stations, 0.08);

    // Calculate proportional radius to indicate magnitude (Duncan-Parnell bubble map technique)
    const getMarkerRadius = (station: GisStationData): number => {
        if (station.radius) return station.radius;
        if (station.value === null || isNaN(station.value)) return 6.5;
        
        const val = Math.abs(station.value);
        if (station.unit === '%') {
            return 5 + Math.min((val / 100) * 12, 12);
        }
        if (station.unit === 'm³/s' || station.unit === 'm3/s') {
            return 5 + Math.min(Math.log1p(val) * 2.2, 12);
        }
        if (station.unit === 'mm') {
            return 5 + Math.min(Math.log1p(val) * 2.5, 12);
        }
        if (station.unit === 'm') {
            return 5 + Math.min(val * 1.5, 12);
        }
        return 5 + Math.min(Math.log1p(val) * 2.0, 11);
    };

    // Style for transboundary basin layers (Incomati)
    const styleIncomati = (feature: any) => {
        return {
            fillColor: 'rgb(5,196,188)', // Incomati teal
            fillOpacity: 0.04,
            color: 'rgb(5,196,188)',
            weight: 2.2,
            opacity: 0.85,
        };
    };

    // Style for transboundary basin layers (Maputo)
    const styleMaputo = (feature: any) => {
        return {
            fillColor: 'rgb(210,109,84)', // Maputo terracotta
            fillOpacity: 0.04,
            color: 'rgb(210,109,84)',
            weight: 2.2,
            opacity: 0.85,
        };
    };

    const onEachBasinFeature = (feature: any, layer: any, isMaputo: boolean) => {
        layer.on({
            click: (e: any) => {
                L.DomEvent.stopPropagation(e);
            }
        });
    };

    return (
        <div ref={mapContainerRef} className="gis-map-root" style={{
            position: 'relative',
            height,
            width: '100%',
            borderRadius: isFullscreen ? 0 : 16,
            overflow: 'hidden',
            border: '1px solid var(--mantine-color-default-border)',
        }}>
            <style dangerouslySetInnerHTML={{ __html: `
                .gis-map-root:fullscreen { height: 100dvh; }
                .gis-map-root:-webkit-full-screen { height: 100dvh; }
                @keyframes cluster-pulse {
                    0% { transform: scale(0.95); opacity: 0.95; }
                    50% { transform: scale(1.1); opacity: 0.35; }
                    100% { transform: scale(0.95); opacity: 0.95; }
                }
                @keyframes gis-alert-pulse {
                    0%, 100% { opacity: 0.35; }
                    50% { opacity: 0; }
                }
                .gis-alert-pulse { animation: gis-alert-pulse 2s infinite ease-in-out; }
            `}} />
            {/* Alternative Text Narrative for Screen Readers (Esri Accessibility requirement) */}
            <div
                style={{
                    position: 'absolute',
                    width: '1px',
                    height: '1px',
                    padding: 0,
                    margin: '-1px',
                    overflow: 'hidden',
                    clip: 'rect(0, 0, 0, 0)',
                    whiteSpace: 'nowrap',
                    border: 0,
                }}
                aria-live="polite"
            >
                Interactive transboundary water monitoring map representing the Incomati and Maputo river basins.
                Contains {stations.length} monitoring stations.
                The transboundary basin outlines are illustrated.
                Current base map is set to {baseMap === 'muted' ? 'Esri Muted Gray Canvas (High Accessibility)' : baseMap === 'satellite' ? 'Esri Satellite Imagery' : 'OpenStreetMap Street View'}.
                Symbology redundancy (Double Coding) is enabled: station markers scale proportionally to indicate measurement magnitude.
                Outline styles denote compliance: Solid border indicates Compliant, Dashed border indicates Non-Compliant/Alert, and Dotted border indicates No Data.
            </div>

            <MapContainer
                center={DEFAULT_CENTER}
                zoom={7.5}
                scrollWheelZoom
                style={{ height: '100%', width: '100%', zIndex: 1 }}
            >
                <TileLayer
                    key={baseMap}
                    url={baseMap === 'satellite' ? SAT_URL : baseMap === 'muted' ? CANVAS_URL : OSM_URL}
                    attribution={baseMap === 'satellite' ? SAT_ATTR : baseMap === 'muted' ? CANVAS_ATTR : OSM_ATTR}
                />

                {/* Transboundary Basin & Sub-basins Layers */}
                {showBasins && (
                    <>
                        <GeoJSON
                            data={incomati as any}
                            style={styleIncomati}
                            onEachFeature={(feature, layer) => onEachBasinFeature(feature, layer, false)}
                        />
                        <GeoJSON
                            data={maputo as any}
                            style={styleMaputo}
                            onEachFeature={(feature, layer) => onEachBasinFeature(feature, layer, true)}
                        />
                    </>
                )}

                {/* Alert Hotspots layer */}
                {visMode === 'hotspots' && showStations && (
                    <>
                        {stations.map((station) => {
                            const isAlert = station.color === 'rgb(255, 0, 0)' || station.color === '#ff0000';
                            const hotspotRadius = 25 + Math.min(Math.abs(station.value ?? 0) * 1.5, 45);

                            return (
                                <CircleMarker
                                    key={`hotspot-${station.id}`}
                                    center={[station.latitude, station.longitude]}
                                    radius={hotspotRadius}
                                    pathOptions={{
                                        fillColor: isAlert ? '#e03131' : '#2b8a3e',
                                        color: 'transparent',
                                        fillOpacity: 0.18,
                                        weight: 0,
                                    }}
                                >
                                    <LeafletTooltip sticky direction="top">
                                        <strong>{t('map.popup.hotspotZone')} {station.name}</strong><br />
                                        {t('map.popup.value')} {station.value !== null ? `${station.value} ${station.unit}` : t('map.popup.noData')}<br />
                                        {t('map.popup.status')} {isAlert ? t('map.popup.highAlert') : t('map.popup.normalHealthy')}
                                    </LeafletTooltip>
                                </CircleMarker>
                            );
                        })}
                    </>
                )}

                {/* Hexagonal Grid Binning layer */}
                {visMode === 'hex' && showStations && (
                    <>
                        {hexBins.features.map((feature, idx) => {
                            const props = feature.properties;
                            const hasAlerts = props.alertCount > 0;
                            const hasValue = props.avgValue !== null;

                            const fillColor = hasAlerts 
                                ? 'rgba(224, 49, 49, 0.42)' 
                                : !hasValue 
                                    ? 'rgba(108, 117, 125, 0.22)' 
                                    : 'rgba(43, 138, 62, 0.38)';

                            const strokeColor = hasAlerts 
                                ? '#e03131' 
                                : !hasValue 
                                    ? '#6c757d' 
                                    : '#2b8a3e';

                            return (
                                <GeoJSON
                                    key={`hex-${idx}`}
                                    data={{
                                        type: 'Feature',
                                        properties: props,
                                        geometry: feature.geometry
                                    } as any}
                                    style={{
                                        fillColor,
                                        color: strokeColor,
                                        weight: 1.8,
                                        opacity: 0.9,
                                        fillOpacity: 0.7,
                                    }}
                                >
                                    <LeafletTooltip sticky direction="top">
                                        <strong>{t('map.popup.hexBin')} ({props.count} {t('map.popup.gauges')})</strong><br />
                                        {t('map.popup.gaugesLabel')} {props.stationCodes.join(', ')}<br />
                                        {t('map.popup.avgValue')} {props.avgValue !== null ? `${props.avgValue.toFixed(2)}` : t('map.popup.noData')}<br />
                                        {t('map.popup.status')} {hasAlerts ? `${props.alertCount} ${t('map.popup.alertsActive')}` : t('map.popup.healthy')}
                                    </LeafletTooltip>
                                    <Popup>
                                        <Card p="xs" radius="md" style={{ minWidth: 220 }}>
                                            <Text fw={700} size="xs" c={hasAlerts ? 'red.7' : 'green.7'} ta="center" mb="xs" style={{ textTransform: 'uppercase' }}>
                                                {t('map.popup.hexStations')} ({props.count})
                                            </Text>
                                            <Stack gap={6}>
                                                {props.stations.map((st: any) => (
                                                    <div key={st.id} style={{ borderBottom: '1px solid #e9ecef', paddingBottom: 4 }}>
                                                        <Group justify="space-between" wrap="nowrap">
                                                            <Text size="xs" fw={700}>{st.code}</Text>
                                                            <span style={{ width: 8, height: 8, borderRadius: '50%', backgroundColor: st.color }} />
                                                        </Group>
                                                        <Text size="10px" c="dimmed" lineClamp={1}>{st.name}</Text>
                                                        <Text size="10px">{t('map.popup.value')} <span style={{ fontWeight: 600, color: st.color }}>{st.value !== null ? `${st.value} ${st.unit}` : t('map.popup.noData')}</span></Text>
                                                    </div>
                                                ))}
                                            </Stack>
                                        </Card>
                                    </Popup>
                                </GeoJSON>
                            );
                        })}
                    </>
                )}

                <BoundsUpdater stations={stations} />

                <ScaleControl position="bottomleft" imperial={false} />
                <CoordDisplay divRef={coordDisplayRef} />
                <FullscreenResizer isFullscreen={isFullscreen} />

                {/* Stations Markers (Point Map with dynamic sizes, clustering & outline double coding for CVD accessibility) */}
                {visMode === 'badges' && showStations && (
                    <ClusteredMarkers stations={stations} getMarkerRadius={getMarkerRadius} onMarkerClick={onMarkerClick} />
                )}
            </MapContainer>

            {/* Premium Glassmorphic Layer Control Panel */}
            <div style={{ position: 'absolute', top: 12, right: 12, zIndex: 1000 }}>
                <Card 
                    radius="lg" 
                    p="xs" 
                    style={{ 
                        boxShadow: 'var(--mantine-shadow-md)', 
                        border: '1px solid rgba(0,0,0,0.1)', 
                        backgroundColor: 'rgba(255, 255, 255, 0.85)',
                        backdropFilter: 'blur(8px)',
                        minWidth: controlsExpanded ? 240 : 44,
                        transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
                    }}
                >
                    <Group justify="space-between" wrap="nowrap" gap={8}>
                        <Group gap={8} wrap="nowrap" style={{ display: controlsExpanded ? 'flex' : 'none' }}>
                            <IconLayersIntersect size={18} style={{ color: 'var(--mantine-color-blue-filled)' }} />
                            <Text fw={700} size="xs" style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>{t('map.layers')}</Text>
                        </Group>
                        <Group gap={4} wrap="nowrap">
                            <MantineTooltip label={isFullscreen ? t('map.exitFullscreen') : t('map.fullscreen')} position="left" withArrow>
                                <ActionIcon
                                    size="md"
                                    variant="subtle"
                                    color="dark"
                                    radius="md"
                                    onClick={() => {
                                        if (!document.fullscreenElement) {
                                            mapContainerRef.current?.requestFullscreen();
                                        } else {
                                            document.exitFullscreen();
                                        }
                                    }}
                                    aria-label={isFullscreen ? t('map.exitFullscreen') : t('map.fullscreen')}
                                >
                                    {isFullscreen ? <IconMinimize size={16} /> : <IconMaximize size={16} />}
                                </ActionIcon>
                            </MantineTooltip>
                            <ActionIcon
                                size="md"
                                variant="subtle"
                                color="dark"
                                radius="md"
                                onClick={() => setControlsExpanded(v => !v)}
                                aria-label={t('map.toggleLayersPanel')}
                            >
                                {controlsExpanded ? <IconChevronUp size={16} /> : <IconLayersIntersect size={18} />}
                            </ActionIcon>
                        </Group>
                    </Group>

                    <Collapse in={controlsExpanded} mt="xs">
                        <Divider mb="xs" />
                        <Stack gap={10}>
                            <div>
                                <Text fw={700} size="10px" c="dimmed" mb={4} style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>{t('map.baseMap')}</Text>
                                <SegmentedControl
                                    value={baseMap}
                                    onChange={(v) => setBaseMap(v as any)}
                                    data={[
                                        { label: t('map.mapType.map'), value: 'osm' },
                                        { label: t('map.mapType.muted'), value: 'muted' },
                                        { label: t('map.mapType.sat'), value: 'satellite' },
                                    ]}
                                    size="xs"
                                    radius="md"
                                    fullWidth
                                />
                            </div>
                            <Divider my={2} />
                            <div>
                                <Text fw={700} size="10px" c="dimmed" mb={4} style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>{t('map.gisVisualization')}</Text>
                                <SegmentedControl
                                    value={visMode}
                                    onChange={(v) => setVisMode(v as any)}
                                    data={[
                                        { label: t('map.vizType.gauges'), value: 'badges' },
                                        { label: t('map.vizType.hexBins'), value: 'hex' },
                                        { label: t('map.vizType.hotspots'), value: 'hotspots' },
                                    ]}
                                    size="xs"
                                    radius="md"
                                    fullWidth
                                />
                            </div>
                            <Divider my={2} />
                            <Switch
                                label={t('map.basinOutlines')}
                                checked={showBasins}
                                onChange={(event) => setShowBasins(event.currentTarget.checked)}
                                size="xs"
                                radius="md"
                            />
                            <Switch
                                label={t('map.stationMarkers')}
                                checked={showStations}
                                onChange={(event) => setShowStations(event.currentTarget.checked)}
                                size="xs"
                                radius="md"
                            />
                        </Stack>
                    </Collapse>
                </Card>
            </div>

            {/* Mouse coordinate display — updated directly via ref, no React re-renders */}
            <div ref={coordDisplayRef} style={{
                display: 'none',
                position: 'absolute',
                bottom: 40,
                left: '50%',
                transform: 'translateX(-50%)',
                zIndex: 1000,
                backgroundColor: 'rgba(255, 255, 255, 0.88)',
                backdropFilter: 'blur(6px)',
                border: '1px solid rgba(0,0,0,0.1)',
                borderRadius: 20,
                padding: '3px 12px',
                fontSize: 11,
                fontFamily: 'monospace',
                color: '#495057',
                pointerEvents: 'none',
                whiteSpace: 'nowrap',
                boxShadow: '0 1px 4px rgba(0,0,0,0.12)',
            }} />

            {/* Premium Glassmorphic Map Legend */}
            <div
                style={{
                    position: 'absolute',
                    bottom: 12,
                    right: 12,
                    zIndex: 1000,
                    backgroundColor: 'rgba(255, 255, 255, 0.85)',
                    backdropFilter: 'blur(8px)',
                    border: '1px solid rgba(0, 0, 0, 0.1)',
                    borderRadius: 12,
                    padding: '8px 12px',
                    boxShadow: 'var(--mantine-shadow-lg)',
                    maxWidth: 250,
                    pointerEvents: 'auto',
                }}
            >
                <Group
                    justify="space-between"
                    wrap="nowrap"
                    gap={8}
                    style={{ cursor: 'pointer', userSelect: 'none' }}
                    onClick={() => setLegendCollapsed(v => !v)}
                >
                    <Text fw={700} size="xs" style={{ letterSpacing: 0.5, textTransform: 'uppercase' }} c="dark.4">
                        {visMode === 'hex' ? t('map.legend.hexBins') : visMode === 'hotspots' ? t('map.legend.hotspots') : legendTitle}
                        {visMode === 'badges' && alertCount > 0 && (
                            <span style={{ marginLeft: 6, color: '#e03131', fontWeight: 700 }}>
                                — {alertCount} alert{alertCount === 1 ? '' : 's'}
                            </span>
                        )}
                    </Text>
                    <ActionIcon size="xs" variant="subtle" color="dark" radius="sm" aria-label="Toggle legend">
                        {legendCollapsed ? <IconChevronUp size={12} /> : <IconChevronDown size={12} />}
                    </ActionIcon>
                </Group>

                {!legendCollapsed && (
                    <>
                        <Text size="9px" c="dimmed" mt={4} mb={8} style={{ lineHeight: 1.1 }}>
                            {visMode === 'hex'
                                ? t('map.legend.descHexBins')
                                : visMode === 'hotspots'
                                    ? t('map.legend.descHotspots')
                                    : t('map.legend.descGauges')}
                        </Text>
                        <Stack gap={6}>
                            {visMode === 'hex' ? (
                        <>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <span style={{ display: 'inline-block', width: 14, height: 14, border: '1.8px solid #2b8a3e', backgroundColor: 'rgba(43, 138, 62, 0.25)', borderRadius: 2 }} />
                                <Text size="xs" c="dark.3">{t('map.legend.compliant')}</Text>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <span style={{ display: 'inline-block', width: 14, height: 14, border: '1.8px solid #e03131', backgroundColor: 'rgba(224, 49, 49, 0.35)', borderRadius: 2 }} />
                                <Text size="xs" c="dark.3">{t('map.legend.nonCompliant')}</Text>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <span style={{ display: 'inline-block', width: 14, height: 14, border: '1.8px solid #868e96', backgroundColor: 'rgba(134, 142, 150, 0.15)', borderRadius: 2 }} />
                                <Text size="xs" c="dark.3">{t('map.legend.noValue')}</Text>
                            </div>
                        </>
                    ) : visMode === 'hotspots' ? (
                        <>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <span style={{ display: 'inline-block', width: 14, height: 14, borderRadius: '50%', backgroundColor: 'rgba(224, 49, 49, 0.18)', border: '1px solid #e03131' }} />
                                <Text size="xs" c="dark.3">{t('map.legend.alertHotspot')}</Text>
                            </div>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <span style={{ display: 'inline-block', width: 14, height: 14, borderRadius: '50%', backgroundColor: 'rgba(43, 138, 62, 0.18)', border: '1px solid #2b8a3e' }} />
                                <Text size="xs" c="dark.3">{t('map.legend.healthyHotspot')}</Text>
                            </div>
                        </>
                    ) : (
                        legends.filter(leg => !leg.label.includes("Basin Outline")).map((leg, idx) => {
                            const isAlert = leg.color === 'rgb(255, 0, 0)';
                            const isNoData = leg.color === 'rgb(127, 127, 127)';
                            
                            return (
                                <div key={idx} style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                    <span
                                        style={{
                                            display: 'inline-block',
                                            width: 14,
                                            height: 14,
                                            borderRadius: '50%',
                                            backgroundColor: leg.color,
                                            border: isAlert ? '2px dashed #c92a2a' : isNoData ? '1.2px dotted #495057' : '1.5px solid #106ba3',
                                        }}
                                    />
                                    <Text size="xs" c="dark.3" style={{ lineHeight: 1.1 }}>
                                        {leg.label} ({isAlert ? t('map.legend.borderDashed') : isNoData ? t('map.legend.borderDotted') : t('map.legend.borderSolid')})
                                    </Text>
                                </div>
                            );
                        })
                    )}
                    <Divider my={4} />
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <span style={{ display: 'inline-block', width: 14, height: 4, backgroundColor: 'rgb(5,196,188)', borderRadius: 2 }} />
                        <Text size="xs" c="dark.3">{t('map.legend.incoatiBound')}</Text>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                        <span style={{ display: 'inline-block', width: 14, height: 4, backgroundColor: 'rgb(210,109,84)', borderRadius: 2 }} />
                        <Text size="xs" c="dark.3">{t('map.legend.maputoBound')}</Text>
                    </div>
                </Stack>
                    </>
                )}
            </div>

            {/* Station Edit / Page-Specific Drawer — rendered inside container (withinPortal=false) so it is visible in fullscreen */}
            {renderFullscreenDrawer
                ? isFullscreen && renderFullscreenDrawer(selectedId ?? null, onDeselect ?? (() => {}))
                : <StationEditDrawer
                    station={isFullscreen ? (stations.find((s) => s.id === selectedId) as unknown as StationMapMarker) ?? null : null}
                    onClose={onDeselect ?? (() => {})}
                    canManage={canManage}
                  />}
        </div>
    );
}
