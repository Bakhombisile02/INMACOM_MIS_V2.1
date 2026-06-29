export interface HexBinFeature {
    type: 'Feature';
    properties: {
        hexId: string;
        count: number;
        avgValue: number | null;
        alertCount: number;
        stationCodes: string[];
        stations: any[];
    };
    geometry: {
        type: 'Polygon';
        coordinates: [number, number][][];
    };
}

export interface HexBinCollection {
    type: 'FeatureCollection';
    features: HexBinFeature[];
}

/**
 * Generates a set of hexagonal bins from point data points.
 * Point format: { latitude: number, longitude: number, value: number | null, code: string, ... }
 * @param points Array of data points containing latitude, longitude, and value
 * @param hexSize Spatial spacing size in degrees (default 0.35)
 */
export function generateHexBins(points: any[], hexSize: number = 0.35): HexBinCollection {
    const bins: Record<string, {
        points: any[];
        count: number;
        sumValue: number;
        valueCount: number;
        alertCount: number;
        stationCodes: string[];
    }> = {};

    // Pointy-topped hex grid math
    // Hex size is 'r' (radius from center to vertex)
    const r = hexSize;
    const hexWidth = Math.sqrt(3) * r;
    const hexHeight = 2 * r;
    const horizDist = hexWidth;
    const vertDist = (3 / 2) * r;

    points.forEach((pt) => {
        const lat = pt.latitude;
        const lng = pt.longitude;
        if (lat === undefined || lng === undefined) return;

        // Approximate mapping to axial hex coordinates (q, r)
        // Convert screen-like coordinates (lng, lat) to hex
        const x = lng;
        const y = lat;

        // Using simple grid approximation for hex centers
        const gridCellR = Math.round(y / vertDist);
        // Offset rows horizontally to create hex offset
        const gridCellQ = Math.round((x - (gridCellR % 2 === 0 ? 0 : horizDist / 2)) / horizDist);

        // Center point of this hex cell
        const centerY = gridCellR * vertDist;
        const centerX = gridCellQ * horizDist + (gridCellR % 2 === 0 ? 0 : horizDist / 2);

        const hexId = `${gridCellQ}_${gridCellR}`;

        if (!bins[hexId]) {
            bins[hexId] = {
                points: [],
                count: 0,
                sumValue: 0,
                valueCount: 0,
                alertCount: 0,
                stationCodes: [],
            };
        }

        bins[hexId].points.push(pt);
        bins[hexId].count++;
        bins[hexId].stationCodes.push(pt.code);

        if (pt.value !== null && pt.value !== undefined) {
            bins[hexId].sumValue += pt.value;
            bins[hexId].valueCount++;
        }

        // Alert check: if a station has a custom status or is in non-compliant status
        const isAlert = pt.color === 'rgb(255, 0, 0)' || pt.color === 'rgb(255, 192, 0)' || pt.is_alert || (pt.value !== null && pt.limit !== undefined && pt.limit !== null && pt.value > pt.limit);
        if (isAlert) {
            bins[hexId].alertCount++;
        }
    });

    const features: HexBinFeature[] = Object.entries(bins).map(([hexId, data]) => {
        const coords = hexId.split('_');
        const gridCellQ = parseInt(coords[0], 10);
        const gridCellR = parseInt(coords[1], 10);

        const centerY = gridCellR * vertDist;
        const centerX = gridCellQ * horizDist + (gridCellR % 2 === 0 ? 0 : horizDist / 2);

        // Generate 6 hexagon corners
        const hexagonCorners: [number, number][] = [];
        for (let i = 0; i < 6; i++) {
            const angleDeg = 60 * i - 30; // Pointy topped offset angle
            const angleRad = (Math.PI / 180) * angleDeg;
            const x = centerX + r * Math.cos(angleRad);
            const y = centerY + r * Math.sin(angleRad);
            hexagonCorners.push([x, y]);
        }
        // Close the polygon
        hexagonCorners.push([hexagonCorners[0][0], hexagonCorners[0][1]]);

        const avgValue = data.valueCount > 0 ? data.sumValue / data.valueCount : null;

        return {
            type: 'Feature',
            properties: {
                hexId,
                count: data.count,
                avgValue,
                alertCount: data.alertCount,
                stationCodes: data.stationCodes,
                stations: data.points,
            },
            geometry: {
                type: 'Polygon',
                coordinates: [hexagonCorners],
            },
        };
    });

    return {
        type: 'FeatureCollection',
        features,
    };
}
