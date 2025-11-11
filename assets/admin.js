/* global ol */
(function($){
    'use strict';
    
    // Global error handler for async issues in admin
    window.addEventListener('error', function(e) {
        if (e.error && e.error.message && e.error.message.includes('message channel closed')) {
            e.preventDefault();
            console.warn('Suppressed admin message channel error');
            return false;
        }
    });
    
    let map, draw, source, vectorLayer, modify, selectInteraction, selectedFeature = null;
    let addressRowIndex = 0;

    function init() {
        // Check if required libraries are available
        if (typeof ol === 'undefined') {
            console.error('OpenLayers not loaded');
            return;
        }
        
        if (typeof $ === 'undefined') {
            console.error('jQuery not loaded');
            return;
        }
        
        try {
        source = new ol.source.Vector();
        vectorLayer = new ol.layer.Vector({
            source: source,
            style: function(feature) {
                const props = feature.getProperties() || {};
                const color = props.color || '#007bff';
                const isCircle = props.geometry_type === 'Circle';
                
                let style = new ol.style.Style({
                    fill: new ol.style.Fill({ color: colorToRgba(color, 0.18) }),
                    stroke: new ol.style.Stroke({ 
                        color: color, 
                        width: isCircle ? 3 : 2,
                        lineDash: isCircle ? [8, 8] : undefined
                    }),
                    image: new ol.style.Circle({ radius: 6, fill: new ol.style.Fill({ color: color }) })
                });
                
                return style;
            }
        });

        map = new ol.Map({
            target: 'coverage-admin-map',
            layers: [
                new ol.layer.Tile({ source: new ol.source.OSM() }),
                vectorLayer
            ],
            view: new ol.View({ center: ol.proj.fromLonLat([0,0]), zoom: 2 })
        });

        modify = new ol.interaction.Modify({ source: source });
        map.addInteraction(modify);

        // select interaction for editing properties
        selectInteraction = new ol.interaction.Select();
        map.addInteraction(selectInteraction);
        selectInteraction.on('select', function(e){
            const features = e.target.getFeatures();
            if (features && features.getLength && features.getLength() > 0) {
                const f = features.item(0);
                
                // Center map on selected feature
                centerMapOnFeature(f);
                
                // Open properties panel
                openPropsPanel(f);
            } else {
                closePropsPanel();
            }
        });

        // Add double-click to center on feature
        map.on('dblclick', function(evt) {
            const feature = map.forEachFeatureAtPixel(evt.pixel, function(feature) {
                return feature;
            });
            if (feature) {
                centerMapOnFeature(feature);
                selectInteraction.getFeatures().clear();
                selectInteraction.getFeatures().push(feature);
                openPropsPanel(feature);
            }
        });

        // Set up initial address row index
        addressRowIndex = Math.max(0, $('.address-row').length);

        // load existing geojson
        const hidden = document.getElementById('coverage-geojson');
        if ( hidden && hidden.value ) {
            try {
                const format = new ol.format.GeoJSON();
                const obj = JSON.parse(hidden.value);
                const feats = format.readFeatures(obj, { featureProjection: 'EPSG:3857' });
                
                // Process features to restore circles if needed
                feats.forEach(function(feature) {
                    const props = feature.getProperties() || {};
                    if (props.geometry_type === 'Circle' && props.circle_center && props.circle_radius) {
                        // This was originally a circle, keep it as polygon but maintain circle properties
                        feature.set('geometry_type', 'Circle');
                    }
                });
                
                source.addFeatures(feats);
                // zoom to features with improved extent calculation
                if (feats.length > 0) {
                    let totalExtent = ol.extent.createEmpty();
                    
                    feats.forEach(function(feature) {
                        const props = feature.getProperties() || {};
                        let extent;
                        
                        // Handle circles specially for better initial view
                        if (props.geometry_type === 'Circle' && props.circle_center && props.circle_radius) {
                            const center = props.circle_center;
                            const radius = props.circle_radius;
                            extent = [
                                center[0] - radius,
                                center[1] - radius,
                                center[0] + radius,
                                center[1] + radius
                            ];
                        } else {
                            extent = feature.getGeometry().getExtent();
                        }
                        
                        ol.extent.extend(totalExtent, extent);
                    });
                    
                    if (!ol.extent.isEmpty(totalExtent)) {
                        map.getView().fit(totalExtent, { 
                            padding: [50, 50, 50, 50],
                            maxZoom: 14
                        });
                    }
                }
            } catch(e) {
                console.warn('Invalid geojson in meta:', e);
            }
        }

        // save on modify
        modify.on('modifyend', saveGeoJSON);

        // when a feature is removed/added/changed, keep props panel in sync
        source.on(['addfeature','removefeature','changefeature'], function(){
            saveGeoJSON();
        });

        $('#coverage-draw-point').on('click', function(){ startDraw('Point'); });
        $('#coverage-draw-polygon').on('click', function(){ startDraw('Polygon'); });
        $('#coverage-draw-circle').on('click', function(){ startDraw('Circle'); });
        $('#coverage-clear').on('click', function(){ 
            try {
                source.clear(); 
                saveGeoJSON(); 
                closePropsPanel(); 
                selectInteraction.getFeatures().clear();
                console.log('Map cleared successfully');
            } catch (error) {
                console.error('Error clearing map:', error);
            }
        });

        // props panel buttons
        $('#coverage-prop-save').on('click', function(){ savePropsToFeature(); });
        $('#coverage-prop-center').on('click', function(){ 
            if (selectedFeature) { 
                centerMapOnFeature(selectedFeature); 
            } 
        });
        $('#coverage-prop-delete').on('click', function(){ 
            if (selectedFeature) { 
                source.removeFeature(selectedFeature); 
                selectedFeature = null; 
                selectInteraction.getFeatures().clear();
                saveGeoJSON(); 
                closePropsPanel(); 
            } 
        });
        $('#coverage-prop-close').on('click', function(){ closePropsPanel(); selectInteraction.getFeatures().clear(); });

        // Address management
        setupAddressManagement();
        
        } catch (error) {
            console.error('Error initializing admin map:', error);
        }
    }

    function setupAddressManagement() {
        // Add street button
        $('#add-address-row').on('click', function() {
            addAddressRow();
        });

        // Event delegation for dynamically created elements
        $(document).on('click', '.remove-address-row', function() {
            $(this).closest('.address-row').remove();
        });

        $(document).on('click', '.add-number', function() {
            addNumberRow($(this).closest('.address-row'));
        });

        $(document).on('click', '.remove-number', function() {
            $(this).closest('.number-row').remove();
        });
    }

    function addAddressRow() {
        const container = $('#address-rows-container');
        const newIndex = addressRowIndex++;
        const html = `
            <div class="address-row" data-index="${newIndex}">
                <div class="address-row-header">
                            <input type="text" class="street-input" name="addresses[${newIndex}][street]" value="" placeholder="Küçə adı" style="width: 300px; margin-right: 10px;" />
                            <button type="button" class="button remove-address-row" style="margin-left: 10px; color: #a00;">Küçəni Sil</button>
                </div>
                <div class="numbers-container" style="margin-left: 20px; margin-top: 8px;">
                    <div class="add-number-container">
                                                        <button type="button" class="button add-number">Nömrə Əlavə Et</button>
                    </div>
                </div>
            </div>
        `;
        container.append(html);
    }

    function addNumberRow($addressRow) {
        const index = $addressRow.data('index');
        const $numbersContainer = $addressRow.find('.numbers-container');
        const $addContainer = $numbersContainer.find('.add-number-container');
        const numberIndex = $numbersContainer.find('.number-row').length;
        
        const html = `
            <div class="number-row">
                <input type="text" class="number-input" name="addresses[${index}][numbers][${numberIndex}]" value="" placeholder="Nömrə" style="width: 80px; margin-right: 10px;" />
                <button type="button" class="button remove-number" style="margin-left: 10px; color: #a00;">Sil</button>
            </div>
        `;
        
        $addContainer.before(html);
    }

    function centerMapOnFeature(feature) {
        if (!feature) return;
        
        const props = feature.getProperties() || {};
        const geom = feature.getGeometry();
        
        if (!geom) return;
        
        // Handle circle features specially
        if (props.geometry_type === 'Circle' && props.circle_center && props.circle_radius) {
            // For circles, use the original center and radius for better centering
            const center = props.circle_center;
            const radius = props.circle_radius;
            
            // Create a buffer around the center for proper zoom
            const padding = radius * 0.5; // 50% padding
            const extent = [
                center[0] - radius - padding,
                center[1] - radius - padding,
                center[0] + radius + padding,
                center[1] + radius + padding
            ];
            
            map.getView().fit(extent, {
                duration: 500,
                padding: [50, 50, 50, 50]
            });
        } else {
            // For other geometries, use the standard extent
            const extent = geom.getExtent();
            if (!ol.extent.isEmpty(extent)) {
                map.getView().fit(extent, {
                    duration: 500,
                    padding: [50, 50, 50, 50],
                    maxZoom: 16 // Prevent zooming too close
                });
            }
        }
    }

    function startDraw(type) {
        try {
            if (draw) {
                map.removeInteraction(draw);
                draw = null;
            }
            
            if (type === 'Circle') {
                // For circles, use a special interaction that creates polygons
                draw = new ol.interaction.Draw({ 
                    source: source, 
                    type: 'Circle'
                });
            } else {
                draw = new ol.interaction.Draw({ source: source, type: type });
            }
            
            draw.on('drawend', function(evt){
                try {
                    const feature = evt.feature;
                    const geom = feature.getGeometry();
                    
                    // Set default properties
                    feature.set('title', '');
                    feature.set('color', '#007bff');
                    
                    // Handle Circle geometry specially
                    if (geom && geom.getType && geom.getType() === 'Circle') {
                        const center = geom.getCenter();
                        const radius = geom.getRadius();
                        
                        // Convert circle to polygon for GeoJSON compatibility
                        const polygon = new ol.geom.Polygon.fromCircle(geom, 64);
                        feature.setGeometry(polygon);
                        
                        // Store circle info as properties for later use
                        const centerLonLat = ol.proj.toLonLat(center);
                        const p2 = ol.proj.toLonLat([center[0] + radius, center[1]]);
                        const rMeters = haversineDistance(centerLonLat[1], centerLonLat[0], p2[1], p2[0]);
                        
                        feature.set('radius_m', Math.round(rMeters));
                        feature.set('circle_center', center);
                        feature.set('circle_radius', radius);
                        feature.set('geometry_type', 'Circle');
                        
                        console.log('Circle created with radius:', Math.round(rMeters), 'meters');
                    }
                    
                    // Center map and open properties panel
                    centerMapOnFeature(feature);
                    openPropsPanel(feature);
                    
                    // Clean up drawing interaction
                    map.removeInteraction(draw); 
                    draw = null; 
                    saveGeoJSON();
                    
                } catch (error) {
                    console.error('Error handling draw end:', error);
                }
            });
            
            map.addInteraction(draw);
            
        } catch (error) {
            console.error('Error starting draw interaction:', error);
        }
    }

    function saveGeoJSON() {
        try {
            const format = new ol.format.GeoJSON();
            const feats = source.getFeatures();
            const geojson = format.writeFeaturesObject(feats, { featureProjection: 'EPSG:3857' });
            document.getElementById('coverage-geojson').value = JSON.stringify(geojson);
        } catch (error) {
            console.error('Error saving GeoJSON:', error);
        }
    }

    function openPropsPanel(feature) {
        selectedFeature = feature;
        const panel = document.getElementById('coverage-props');
        if (!panel) return;
        const titleEl = document.getElementById('coverage-prop-title');
        const radiusEl = document.getElementById('coverage-prop-radius');
        const colorEl = document.getElementById('coverage-prop-color');
        const props = feature.getProperties() || {};
        
        titleEl.value = props.title || '';
        radiusEl.value = props.radius_m || '';
        colorEl.value = props.color || '#007bff';
        
        // Show different title for circle features
        const panelTitle = panel.querySelector('h4');
        if (panelTitle) {
            if (props.geometry_type === 'Circle') {
                panelTitle.textContent = 'Dairə Xüsusiyyətləri';
            } else {
                panelTitle.textContent = 'Xüsusiyyətlər';
            }
        }
        
        // Show/hide radius field based on geometry type
        const radiusContainer = radiusEl.closest('p');
        if (radiusContainer) {
            if (props.geometry_type === 'Circle') {
                radiusContainer.style.display = 'block';
            } else {
                radiusContainer.style.display = props.radius_m ? 'block' : 'none';
            }
        }
        
        panel.style.display = 'block';
    }

    function closePropsPanel() {
        const panel = document.getElementById('coverage-props');
        if (panel) panel.style.display = 'none';
        selectedFeature = null;
    }

    function savePropsToFeature() {
        if (!selectedFeature) return;
        const title = document.getElementById('coverage-prop-title').value;
        const radius = document.getElementById('coverage-prop-radius').value;
        const color = document.getElementById('coverage-prop-color').value;
        
        selectedFeature.set('title', title);
        selectedFeature.set('color', color);
        
        if (radius !== '') {
            const radiusNum = Number(radius);
            selectedFeature.set('radius_m', radiusNum);
            
            // If this is a circle, update the geometry based on new radius
            const props = selectedFeature.getProperties() || {};
            if (props.geometry_type === 'Circle' && props.circle_center) {
                const center = props.circle_center;
                const centerLonLat = ol.proj.toLonLat(center);
                // Convert meters to map units (approximate)
                const lat = centerLonLat[1];
                const metersPerUnit = 111320 * Math.cos(lat * Math.PI / 180); // approximate meters per degree
                const radiusInMapUnits = radiusNum / metersPerUnit;
                const radiusInProjected = radiusInMapUnits * (Math.PI / 180) * 6378137; // rough conversion
                
                // Create new circle geometry and convert to polygon
                const circleGeom = new ol.geom.Circle(center, radiusInProjected);
                const polygon = new ol.geom.Polygon.fromCircle(circleGeom, 64);
                selectedFeature.setGeometry(polygon);
                
                selectedFeature.set('circle_radius', radiusInProjected);
            }
        } else {
            selectedFeature.unset('radius_m');
        }
        
        // Trigger style update
        selectedFeature.changed();
        saveGeoJSON();
        closePropsPanel();
    }

    // small helper to convert hex color to rgba
    function colorToRgba(hex, alpha){
        if (!hex) return 'rgba(0,123,255,'+alpha+')';
        const c = hex.replace('#','');
        const bigint = parseInt(c, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return 'rgba('+r+','+g+','+b+','+alpha+')';
    }

    // haversine (lat1, lon1, lat2, lon2) in meters
    function haversineDistance(lat1, lon1, lat2, lon2) {
        function toRad(x){ return x * Math.PI / 180; }
        const R = 6371000; // meters
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    $(document).ready(function(){ 
        // Use setTimeout to ensure all resources are loaded
        setTimeout(function() {
            try {
                init(); 
            } catch (error) {
                console.error('Error initializing admin interface:', error);
            }
        }, 100);
    });
})(jQuery);
