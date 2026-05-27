import { useEffect, useState } from 'react';
import L from 'leaflet';
import { ActionIcon, Alert, Card, Stack, Text, Switch, Group, Divider, Collapse, SegmentedControl, Tooltip } from '@mantine/core';
import { IconMap, IconSatellite, IconLayersIntersect, IconChevronDown, IconChevronUp, IconMapPinOff } from '@tabler/icons-react';
import { GeoJSON, MapContainer, Marker, Popup, TileLayer, Tooltip as LeafletTooltip } from 'react-leaflet';
import { incomati, maputo } from '@/assets/basinBoundaries';

interface StationMapProps {
    latitude?: number | null;
    longitude?: number | null;
    label: string;
    code?: string;
    status?: 'active' | 'inactive';
    summary?: string | null;
    noCoordinatesLabel: string;
    height?: number;
}

const OSM_URL = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const SAT_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
const SAT_ATTR = 'Tiles &copy; <a href="https://www.esri.com">Esri Imagery</a>';
const CANVAS_URL = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';
const CANVAS_ATTR = 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ';

function getStationIcon(code: string, status: 'active' | 'inactive', summary: string | null | undefined) {
    const isUnconfirmed = summary?.includes('Unconfirmed') || summary?.includes('Unverified') || summary?.includes('Inferred');
    
    let bgColor = '#2b8a3e'; // green
    if (isUnconfirmed) {
        bgColor = '#d97706'; // amber
    } else if (status === 'inactive') {
        bgColor = '#c92a2a'; // red
    }

    const displayCode = code.slice(0, 3);

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

export default function StationMap({
    latitude,
    longitude,
    label,
    code = 'STN',
    status = 'active',
    summary,
    noCoordinatesLabel,
    height = 420,
}: StationMapProps) {
    const [baseMap, setBaseMap] = useState<'osm' | 'muted' | 'satellite'>('muted');
    const [showBasins, setShowBasins] = useState(true);
    const [showStations, setShowStations] = useState(true);
    const [controlsExpanded, setControlsExpanded] = useState(false);

    const hasCoordinates = Number.isFinite(latitude) && Number.isFinite(longitude);

    if (!hasCoordinates) {
        return (
            <Alert color="yellow" icon={<IconMapPinOff size={16} />}>
                {noCoordinatesLabel}
            </Alert>
        );
    }

    const center: [number, number] = [Number(latitude), Number(longitude)];
    const isUnconfirmed = summary?.includes('Unconfirmed') || summary?.includes('Unverified') || summary?.includes('Inferred');

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

    const stationIcon = getStationIcon(code, status, summary);

    return (
        <div style={{ position: 'relative', height, width: '100%', borderRadius: 16, overflow: 'hidden', border: '1px solid var(--mantine-color-default-border)', zIndex: 1 }}>
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
                Interactive transboundary water station details map.
                Contains station {code} — {label}.
                The transboundary basin outlines are illustrated.
                Current base map is set to {baseMap === 'muted' ? 'Esri Muted Gray Canvas (High Accessibility)' : baseMap === 'satellite' ? 'Esri Satellite Imagery' : 'OpenStreetMap Street View'}.
                Symbology redundancy (Double Coding) is enabled: circular code marker color indicates operational status.
            </div>

            <MapContainer
                center={center}
                zoom={11}
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

                {/* Single Station Marker with custom circular station-code representation */}
                {showStations && (
                    <Marker position={center} icon={stationIcon}>
                        <LeafletTooltip direction="top" offset={[0, -20]} opacity={0.95}>
                            <strong>{code}</strong> — {label}<br />
                            <span style={{ color: isUnconfirmed ? '#d97706' : '#2b8a3e', fontWeight: 600 }}>
                                {isUnconfirmed ? 'Unconfirmed' : 'Confirmed Data'}
                            </span>
                        </LeafletTooltip>
                        <Popup>
                            <Card p={0} shadow="none" radius="md">
                                <Text fw={700} size="sm" c="blue" mb={4} ff="monospace">
                                    {code}
                                </Text>
                                <Text size="xs" fw={600} mb={4}>
                                    {label}
                                </Text>
                                {isUnconfirmed && (
                                    <Text size="10px" c="orange.8" fw={700} mb={4}>
                                        Inferred Data (Unconfirmed)
                                    </Text>
                                )}
                                <Text size="10px" c="dimmed">
                                    Lat: {latitude?.toFixed(6)}, Lng: {longitude?.toFixed(6)}
                                </Text>
                            </Card>
                        </Popup>
                    </Marker>
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
                            <Text fw={700} size="xs" style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>Map Layers</Text>
                        </Group>
                        <ActionIcon
                            size="md"
                            variant="subtle"
                            color="dark"
                            radius="md"
                            onClick={() => setControlsExpanded(v => !v)}
                            aria-label="Toggle map layer options panel"
                        >
                            {controlsExpanded ? <IconChevronUp size={16} /> : <IconLayersIntersect size={18} />}
                        </ActionIcon>
                    </Group>

                    <Collapse in={controlsExpanded} mt="xs">
                        <Divider mb="xs" />
                        <Stack gap={10}>
                            <div>
                                <Text fw={700} size="10px" c="dimmed" mb={4} style={{ textTransform: 'uppercase', letterSpacing: 0.5 }}>Base Map</Text>
                                <SegmentedControl
                                    value={baseMap}
                                    onChange={(v) => setBaseMap(v as any)}
                                    data={[
                                        { label: 'Map', value: 'osm' },
                                        { label: 'Muted', value: 'muted' },
                                        { label: 'Sat', value: 'satellite' },
                                    ]}
                                    size="xs"
                                    radius="md"
                                    fullWidth
                                />
                            </div>
                            <Divider my={2} />
                            <Switch
                                label="Basin Outlines"
                                checked={showBasins}
                                onChange={(event) => setShowBasins(event.currentTarget.checked)}
                                size="xs"
                                radius="md"
                            />
                            <Switch
                                label="Station Marker"
                                checked={showStations}
                                onChange={(event) => setShowStations(event.currentTarget.checked)}
                                size="xs"
                                radius="md"
                            />
                        </Stack>
                    </Collapse>
                </Card>
            </div>
        </div>
    );
}
