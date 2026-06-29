import L from 'leaflet';
import { Text } from '@mantine/core';
import { MapContainer, Marker, TileLayer, useMapEvents } from 'react-leaflet';

const DEFAULT_CENTER: [number, number] = [-25.5, 31.5];

const pinIcon = L.icon({
    iconUrl: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(
        `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#1971c2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0" /></svg>`,
    )}`,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -28],
});

function ClickHandler({ onChange }: { onChange: (lat: number, lng: number) => void }) {
    useMapEvents({
        click(e) {
            onChange(parseFloat(e.latlng.lat.toFixed(6)), parseFloat(e.latlng.lng.toFixed(6)));
        },
    });
    return null;
}

interface CoordPickerProps {
    lat: number | null;
    lng: number | null;
    onChange: (lat: number, lng: number) => void;
    hint?: string;
    height?: number;
}

export default function CoordPicker({
    lat,
    lng,
    onChange,
    hint = 'Click on the map to set coordinates',
    height = 260,
}: CoordPickerProps) {
    const hasPin = lat != null && lng != null;
    const center: [number, number] = hasPin ? [lat, lng] : DEFAULT_CENTER;

    return (
        <div>
            <div
                style={{
                    position: 'relative',
                    zIndex: 1,
                    height,
                    width: '100%',
                    borderRadius: 8,
                    overflow: 'hidden',
                    cursor: 'crosshair',
                    border: '1px solid var(--mantine-color-default-border)',
                }}
            >
                <MapContainer
                    center={center}
                    zoom={hasPin ? 10 : 6}
                    scrollWheelZoom
                    style={{ height: '100%', width: '100%' }}
                >
                    <TileLayer
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    />
                    <ClickHandler onChange={onChange} />
                    {hasPin && <Marker position={[lat, lng]} icon={pinIcon} />}
                </MapContainer>
            </div>
            <Text size="xs" c="dimmed" mt={4}>
                {hint}
            </Text>
        </div>
    );
}
