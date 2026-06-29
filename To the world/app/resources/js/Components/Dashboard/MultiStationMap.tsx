import { useEffect, useState, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import StationEditDrawer from './StationEditDrawer';
import L from 'leaflet';
import { ActionIcon, Card, Tooltip as MantineTooltip, Stack, Text, Switch, Group, Divider, Collapse, SegmentedControl, Badge } from '@mantine/core';
import { IconMap, IconSatellite, IconLayersIntersect, IconChevronDown, IconChevronUp, IconMaximize, IconMinimize } from '@tabler/icons-react';
import { GeoJSON, MapContainer, Marker, Popup, TileLayer, useMap, useMapEvents, Tooltip as LeafletTooltip, ScaleControl } from 'react-leaflet';
import { incomati, maputo } from '@/assets/basinBoundaries';

export interface StationMapMarker {
    id: string;
    code: string;
    name: string;
    latitude: number | null;
    longitude: number | null;
    status: 'active' | 'inactive';
    is_active: boolean;
    is_real_time: boolean;
    country?: string | null;
    category: string;
    water_source: string;
    water_body_type: string;
    summary?: string | null;
    show_url?: string;
    river_basin?: string | null;
    telemetry_system?: string | null;
    gauge_code?: string | null;
    owner_org?: string | null;
}

const OSM_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const SAT_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
const SAT_ATTR = 'Tiles &copy; <a href="https://www.esri.com">Esri Imagery</a>';
const CANVAS_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';
const CANVAS_ATTR = 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ';

// Default center: Incomati basin (Mozambique / South Africa / Eswatini)
const DEFAULT_CENTER: [number, number] = [-25.5, 31.5];

function makePinSvg(stroke: string, size: number, strokeWidth: number, status: 'active' | 'inactive' | 'selected' | 'unconfirmed') {
    // Shell of the pin
    const shell = `<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" fill="${stroke}22" stroke="${stroke}" stroke-width="${strokeWidth}"/>`;
    
    // Status inner symbol for double-coding (CVD friendly)
    let symbol = '';
    if (status === 'active') {
        // A clean checkmark inside active pin
        symbol = `<path d="M9 9l2 2 4-4" stroke="${stroke}" stroke-width="${strokeWidth + 0.5}" stroke-linecap="round" stroke-linejoin="round"/>`;
    } else if (status === 'inactive') {
        // An 'X' cross mark inside inactive pin
        symbol = `<path d="M9 7l6 6M15 7l-6 6" stroke="${stroke}" stroke-width="${strokeWidth + 0.5}" stroke-linecap="round" stroke-linejoin="round"/>`;
    } else if (status === 'unconfirmed') {
        // A clean question mark (?) symbol inside unconfirmed pin
        symbol = `
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="${stroke}" stroke-width="${strokeWidth + 0.2}" stroke-linecap="round" stroke-linejoin="round"/>
            <line x1="12" y1="16.5" x2="12.01" y2="16.5" stroke="${stroke}" stroke-width="${strokeWidth + 0.4}" stroke-linecap="round"/>
        `;
    } else {
        // Selected: solid dot
        symbol = `<circle cx="12" cy="9" r="2.5" fill="${stroke}" stroke="none"/>`;
    }
    
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none">${shell}${symbol}</svg>`;
}

function getStationIcon(station: StationMapMarker, isSelected: boolean) {
    const isUnconfirmed = station.summary?.includes('Unconfirmed') || station.summary?.includes('Unverified') || station.summary?.includes('Inferred');
    
    let bgColor = '#2b8a3e'; // green
    if (isSelected) {
        bgColor = '#1971c2'; // blue
    } else if (isUnconfirmed) {
        bgColor = '#d97706'; // amber
    } else if (station.status === 'inactive') {
        bgColor = '#c92a2a'; // red
    }

    const displayCode = station.code.slice(0, 3);

    const html = `
        <div style="
            position: absolute;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
        ">
            <div style="
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 38px;
                height: 38px;
                border-radius: 50%;
                background-color: ${bgColor};
                border: 2.5px solid #ffffff;
                color: #ffffff;
                font-weight: 800;
                font-size: 10px;
                font-family: monospace;
                box-shadow: 0 3px 6px rgba(0,0,0,0.25);
                white-space: nowrap;
                text-align: center;
                box-sizing: border-box;
                ${isSelected ? 'box-shadow: 0 0 0 4px rgba(25, 113, 194, 0.4), 0 3px 10px rgba(0,0,0,0.3); border-width: 2.5px;' : ''}
            ">
                ${displayCode}
            </div>
        </div>
    `;

    return L.divIcon({
        html,
        className: 'custom-station-code-marker',
        iconSize: [0, 0],
        iconAnchor: [0, 0],
        popupAnchor: [0, -22]
    });
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

// Fits map bounds whenever the visible station list changes
function BoundsUpdater({ stations }: { stations: StationMapMarker[] }) {
    const map = useMap();

    useEffect(() => {
        const coords = stations
            .filter((s) => s.latitude != null && s.longitude != null)
            .map((s) => [s.latitude!, s.longitude!] as [number, number]);

        if (coords.length === 0) return;
        if (coords.length === 1) {
            map.setView(coords[0], 10);
            return;
        }
        map.fitBounds(L.latLngBounds(coords), { padding: [40, 40], maxZoom: 13 });
    }, [map, stations]);

    return null;
}

// Flies to the selected station and zooms in enough to break it out of any cluster
function SelectedStationFocus({ stations, selectedId }: { stations: StationMapMarker[]; selectedId: string | null }) {
    const map = useMap();

    useEffect(() => {
        if (!selectedId) return;
        const station = stations.find((s) => s.id === selectedId);
        if (station?.latitude == null || station?.longitude == null) return;
        map.flyTo(
            [station.latitude, station.longitude],
            Math.max(map.getZoom(), 13),
            { duration: 0.6 },
        );
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedId]);

    return null;
}

// Subcomponent to handle cluster group calculations and map events (Esri Cluster Map Technique)
interface ClusteredMarkersProps {
    stations: StationMapMarker[];
    selectedId: string | null;
    onMarkerClick: (id: string) => void;
}

function ClusteredMarkers({ stations, selectedId, onMarkerClick }: ClusteredMarkersProps) {
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
    const clusters: { [key: string]: StationMapMarker[] } = {};

    stations.forEach((station) => {
        if (station.latitude == null || station.longitude == null) return;
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
            {Object.entries(clusters).map(([key, group]) => {
                const containsUnconfirmed = group.some(s => s.summary?.includes('Unconfirmed') || s.summary?.includes('Unverified') || s.summary?.includes('Inferred'));

                if (group.length === 1) {
                    const station = group[0];
                    const isSelected = station.id === selectedId;
                    const isUnconfirmed = station.summary?.includes('Unconfirmed') || station.summary?.includes('Unverified') || station.summary?.includes('Inferred');
                    const icon = getStationIcon(station, isSelected);

                    return (
                        <Marker
                            key={station.id}
                            position={[station.latitude!, station.longitude!]}
                            icon={icon}
                            zIndexOffset={isSelected ? 1000 : 0}
                            opacity={selectedId && !isSelected ? 0.25 : 1}
                            eventHandlers={{ click: () => onMarkerClick(station.id) }}
                        >
                            <LeafletTooltip direction="top" offset={[0, -20]} opacity={0.95}>
                                <strong>{station.code}</strong> — {station.name}<br />
                                <span style={{ color: isUnconfirmed ? '#d97706' : '#2b8a3e', fontWeight: 600 }}>
                                    {isUnconfirmed ? 'Unconfirmed' : 'Confirmed Data'}
                                </span>
                            </LeafletTooltip>
                            <Popup>
                                <Card p={0} shadow="none" radius="md">
                                    <Text fw={700} size="sm" c="blue" mb={4} ff="monospace">
                                        {station.code}
                                    </Text>
                                    <Text size="xs" fw={600} mb={4}>
                                        {station.name}
                                    </Text>
                                    {isUnconfirmed && (
                                        <Text size="10px" c="orange.8" fw={700} mb={4}>
                                            Inferred Data (Unconfirmed)
                                        </Text>
                                    )}
                                    <Text size="10px" c="dimmed">
                                        Lat: {station.latitude?.toFixed(4)}, Lng: {station.longitude?.toFixed(4)}
                                    </Text>
                                </Card>
                            </Popup>
                        </Marker>
                    );
                }

                // Cluster marker representation (Esri visual styling with high contrast rings)
                const count = group.length;
                const avgLat = group.reduce((sum, s) => sum + s.latitude!, 0) / count;
                const avgLng = group.reduce((sum, s) => sum + s.longitude!, 0) / count;

                // Detect if all points have identical coordinates (overlapping points)
                const first = group[0];
                const allSameCoord = group.every(s => Math.abs(s.latitude! - first.latitude!) < 0.0001 && Math.abs(s.longitude! - first.longitude!) < 0.0001);

                // Detect status composition (active/inactive) within the cluster
                const activeCount = group.filter(s => s.status === 'active').length;
                const inactiveCount = group.filter(s => s.status === 'inactive').length;
                const containsInactive = inactiveCount > 0;

                // Color themes matching the status palette
                let colorBase = '43, 138, 62'; // Default forest green for active
                if (activeCount === 0 && inactiveCount > 0) {
                    colorBase = '224, 49, 49'; // Alert red for completely inactive
                } else if (containsInactive) {
                    colorBase = '217, 119, 6'; // Amber/Orange for mixed status
                } else if (containsUnconfirmed) {
                    colorBase = '217, 150, 6'; // Vibrant yellow/gold for unconfirmed
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
                        ${containsInactive || containsUnconfirmed ? 'animation: cluster-pulse 1.8s infinite ease-in-out;' : ''}
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

                const containsSelected = selectedId != null && group.some((s) => s.id === selectedId);
                const shouldShowPopup = allSameCoord || zoom >= 14;

                return (
                    <Marker
                        key={key}
                        position={[avgLat, avgLng]}
                        icon={customIcon}
                        opacity={selectedId && !containsSelected ? 0.25 : 1}
                        eventHandlers={{
                            click: (e) => {
                                if (!shouldShowPopup) {
                                    L.DomEvent.stopPropagation(e);
                                    const coords = group.map((s) => [s.latitude!, s.longitude!] as [number, number]);
                                    map.fitBounds(L.latLngBounds(coords), { padding: [40, 40], maxZoom: zoom + 2 });
                                }
                            },
                        }}
                    >
                        {!shouldShowPopup && (
                            <LeafletTooltip direction="top" opacity={0.98}>
                                <div style={{ padding: '8px 12px', minWidth: '220px' }}>
                                    <div style={{ fontWeight: 700, borderBottom: '1px solid #dee2e6', paddingBottom: '4px', marginBottom: '6px', fontSize: '12px' }}>
                                        Cluster: {count} Stations
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                                        {group.slice(0, 8).map((s) => {
                                            const isStationUnconfirmed = s.summary?.includes('Unconfirmed') || s.summary?.includes('Unverified') || s.summary?.includes('Inferred');
                                            return (
                                                <div key={s.id} style={{ fontSize: '11px', lineHeight: '1.3' }}>
                                                    <strong>{s.code}</strong> - {s.name}<br />
                                                    <span style={{ color: '#868e96' }}>Lat: {s.latitude?.toFixed(4)}, Lng: {s.longitude?.toFixed(4)}</span><br />
                                                    <span style={{ color: isStationUnconfirmed ? '#d97706' : '#2b8a3e', fontWeight: 600 }}>
                                                        {isStationUnconfirmed ? 'Unconfirmed' : 'Confirmed'}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                        {group.length > 8 && (
                                            <div style={{ fontSize: '10px', color: '#868e96', fontStyle: 'italic', marginTop: '2px' }}>
                                                + {group.length - 8} more stations... (Click to expand)
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </LeafletTooltip>
                        )}
                        {shouldShowPopup && (
                            <Popup>
                                <Card p="xs" radius="md" style={{ minWidth: 220, maxWidth: 280, border: 'none', boxShadow: 'none' }}>
                                    <Text fw={700} size="xs" c={containsInactive ? 'orange.8' : (containsUnconfirmed ? 'yellow.9' : 'green.8')} ta="center" mb="xs" style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>
                                        Grouped Stations ({count})
                                    </Text>
                                    <Stack gap={6} style={{ maxHeight: 200, overflowY: 'auto' }}>
                                        {group.map((station) => {
                                            const isStationUnconfirmed = station.summary?.includes('Unconfirmed') || station.summary?.includes('Unverified') || station.summary?.includes('Inferred');
                                            return (
                                                <div 
                                                    key={station.id} 
                                                    style={{ 
                                                        borderBottom: '1px solid #e9ecef', 
                                                        paddingBottom: 6, 
                                                        paddingTop: 4, 
                                                        cursor: 'pointer',
                                                        backgroundColor: selectedId === station.id ? '#f1f3f5' : 'transparent',
                                                        paddingLeft: 4,
                                                        paddingRight: 4,
                                                        borderRadius: 4
                                                    }}
                                                    onClick={() => {
                                                        onMarkerClick(station.id);
                                                    }}
                                                >
                                                    <Group justify="space-between" wrap="nowrap" gap={8}>
                                                        <Text size="xs" fw={700} style={{ fontFamily: 'monospace' }} c={selectedId === station.id ? 'blue.7' : 'dark'}>
                                                            {station.code}
                                                        </Text>
                                                        <span style={{ 
                                                            width: 8, 
                                                            height: 8, 
                                                            borderRadius: '50%', 
                                                            backgroundColor: isStationUnconfirmed ? '#d97706' : (station.status === 'active' ? '#2b8a3e' : '#c92a2a'),
                                                            border: station.status === 'inactive' ? '1px solid #c92a2a' : (isStationUnconfirmed ? '1px solid #d97706' : 'none')
                                                        }} />
                                                    </Group>
                                                    <Text size="11px" fw={500} lineClamp={1}>{station.name}</Text>
                                                    <Text size="10px" c="dimmed">
                                                        Status: <span style={{ color: isStationUnconfirmed ? '#d97706' : (station.status === 'active' ? '#2b8a3e' : '#c92a2a'), fontWeight: 600 }}>
                                                            {isStationUnconfirmed ? 'Unconfirmed' : (station.status === 'active' ? 'Active' : 'Inactive')}
                                                        </span>
                                                    </Text>
                                                </div>
                                            );
                                        })}
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

interface MultiStationMapProps {
    stations: StationMapMarker[];
    selectedId: string | null;
    onMarkerClick: (id: string) => void;
    onDeselect?: () => void;
    canManage?: boolean;
    height?: number;
}

export default function MultiStationMap({
    stations,
    selectedId,
    onMarkerClick,
    onDeselect,
    canManage = false,
    height = 520,
}: MultiStationMapProps) {
    // Default to 'muted' Light Gray Canvas for high accessibility and low distraction (Esri guideline)
    const [baseMap, setBaseMap] = useState<'osm' | 'muted' | 'satellite'>('muted');
    const [showBasins, setShowBasins] = useState(true);
    const [showStations, setShowStations] = useState(true);
    const [controlsExpanded, setControlsExpanded] = useState(false);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const mapContainerRef = useRef<HTMLDivElement>(null);
    const coordDisplayRef = useRef<HTMLDivElement>(null);
    const { t } = useTranslation('gis');

    // Use native Fullscreen API — avoids stacking context issues with parent layout
    useEffect(() => {
        const handler = () => {
            setIsFullscreen(!!document.fullscreenElement);
            setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
        };
        document.addEventListener('fullscreenchange', handler);
        return () => document.removeEventListener('fullscreenchange', handler);
    }, []);

    const mappable = stations.filter((s) => s.latitude != null && s.longitude != null);

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
        <div ref={mapContainerRef} className="station-map-root" style={{
            position: 'relative',
            height,
            width: '100%',
            borderRadius: isFullscreen ? 0 : 16,
            overflow: 'hidden',
            border: '1px solid var(--mantine-color-default-border)',
        }}>
            <style dangerouslySetInnerHTML={{ __html: `
                .station-map-root:fullscreen { height: 100dvh; }
                .station-map-root:-webkit-full-screen { height: 100dvh; }
                @keyframes cluster-pulse {
                    0% { transform: scale(0.95); opacity: 0.95; }
                    50% { transform: scale(1.1); opacity: 0.35; }
                    100% { transform: scale(0.95); opacity: 0.95; }
                }
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
                Interactive transboundary water stations registry map representing the Incomati and Maputo river basins.
                Contains {stations.length} monitoring stations.
                The transboundary basin outlines are illustrated.
                Current base map is set to {baseMap === 'muted' ? 'Esri Muted Gray Canvas (High Accessibility)' : baseMap === 'satellite' ? 'Esri Satellite Imagery' : 'OpenStreetMap Street View'}.
                Symbology redundancy (Double Coding) is enabled: active stations are styled as green pin icons containing a checkmark symbol, and inactive stations are styled as red pin icons containing an X cross mark symbol.
            </div>

            <MapContainer
                center={DEFAULT_CENTER}
                zoom={7}
                scrollWheelZoom
                style={{ height: '100%', width: '100%' }}
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

                <BoundsUpdater stations={mappable} />
                <SelectedStationFocus stations={mappable} selectedId={selectedId} />
                <ScaleControl position="bottomleft" imperial={false} />
                <CoordDisplay divRef={coordDisplayRef} />
                <FullscreenResizer isFullscreen={isFullscreen} />

                {/* Station Markers (Point Map with dynamic clustering for CVD-safe accessibility) */}
                {showStations && (
                    <ClusteredMarkers stations={mappable} selectedId={selectedId} onMarkerClick={onMarkerClick} />
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
                        minWidth: controlsExpanded ? 220 : 44,
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

            {/* Station Legend */}
            <div style={{
                position: 'absolute',
                bottom: 12,
                right: 12,
                zIndex: 1000,
                backgroundColor: 'rgba(255, 255, 255, 0.85)',
                backdropFilter: 'blur(8px)',
                border: '1px solid rgba(0,0,0,0.1)',
                borderRadius: 12,
                padding: '8px 12px',
                boxShadow: 'var(--mantine-shadow-sm)',
                minWidth: 130,
            }}>
                <Text fw={700} size="xs" c="dark.4" mb={6} style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>
                    {t('map.stationsLegend')}
                    {stations.length > 0 && (
                        <span style={{ marginLeft: 6, color: '#868e96', fontWeight: 400 }}>
                            ({mappable.length})
                        </span>
                    )}
                </Text>
                <Stack gap={5}>
                    <Group gap={6} wrap="nowrap">
                        <div style={{ width: 10, height: 10, borderRadius: '50%', backgroundColor: '#2f9e44', border: '2px solid #fff', flexShrink: 0, boxShadow: '0 0 0 1px #2f9e44' }} />
                        <Text size="xs" c="dimmed">{t('map.stationStatus.active')}</Text>
                    </Group>
                    <Group gap={6} wrap="nowrap">
                        <div style={{ width: 10, height: 10, borderRadius: '50%', backgroundColor: '#f08c00', border: '2px solid #fff', flexShrink: 0, boxShadow: '0 0 0 1px #f08c00' }} />
                        <Text size="xs" c="dimmed">{t('map.stationStatus.unconfirmed')}</Text>
                    </Group>
                    <Group gap={6} wrap="nowrap">
                        <div style={{ width: 10, height: 10, borderRadius: '50%', backgroundColor: '#e03131', border: '2px solid #fff', flexShrink: 0, boxShadow: '0 0 0 1px #e03131' }} />
                        <Text size="xs" c="dimmed">{t('map.stationStatus.inactive')}</Text>
                    </Group>
                </Stack>
            </div>
            {/* Station Edit Drawer — withinPortal=false so it renders inside the fullscreen element */}
            <StationEditDrawer
                station={isFullscreen ? (stations.find((s) => s.id === selectedId) ?? null) : null}
                onClose={onDeselect ?? (() => {})}
                canManage={canManage}
            />
        </div>
    );
}
