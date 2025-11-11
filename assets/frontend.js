/* global ol, MAP_COVERAGE_DATA, turf */
(function(){
    function init(){
        // Check if MAP_COVERAGE_DATA is available
        if (typeof MAP_COVERAGE_DATA === 'undefined') {
            console.warn('MAP_COVERAGE_DATA not available yet, retrying...');
            setTimeout(init, 100);
            return;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"'`]/g, function(s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;",'`':'&#96;'})[s];
            });
        }
        
        // Helper to convert hex color to rgba
        function colorToRgba(hex, alpha) {
            if (!hex) return 'rgba(0,200,83,' + alpha + ')';
            const c = hex.replace('#','');
            const bigint = parseInt(c, 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
        }
        
        const features = new ol.source.Vector();
        const vectorLayer = new ol.layer.Vector({ 
            source: features, 
            style: function(feature) {
                const props = feature.getProperties() || {};
                const color = props.color || '#00C853';
                return new ol.style.Style({
                    fill: new ol.style.Fill({ color: colorToRgba(color, 0.15) }),
                    stroke: new ol.style.Stroke({ color: color, width: 2 }),
                    image: new ol.style.Circle({ radius: 6, fill: new ol.style.Fill({ color: color }) })
                });
            }
        });
        
        const map = new ol.Map({ target: 'map-coverage-frontend', layers:[ new ol.layer.Tile({ source: new ol.source.OSM() }), vectorLayer ], view: new ol.View({ center: ol.proj.fromLonLat([0,0]), zoom:2 }) });

        // load features from localized data
        try{
            const format = new ol.format.GeoJSON();
            const fc = MAP_COVERAGE_DATA.geojson || { type: 'FeatureCollection', features: [] };
            const feats = format.readFeatures(fc, { featureProjection: 'EPSG:3857' });
            features.addFeatures(feats);
            const extent = features.getExtent();
            if (!ol.extent.isEmpty(extent)) map.getView().fit(extent, { padding: [20,20,20,20] });
        }catch(e){ console.warn(e); }

        // Build dropdowns for regions and addresses
        const posts = MAP_COVERAGE_DATA.posts || [];
        console.log('Posts data:', posts);
        
        const container = document.getElementById('map-coverage-search');
        if (!container) {
            console.error('map-coverage-search container not found!');
            return;
        }

        // Check if dropdowns already exist (from PHP)
        let citySelect = document.getElementById('map-city-select');
        let regionSelect = document.getElementById('map-region-select');
        let streetInput = document.getElementById('map-street-input');
        let streetSuggestions = document.getElementById('map-street-suggestions');
        let numberInput = document.getElementById('map-number-input');
        let numberSuggestions = document.getElementById('map-number-suggestions');
        let checkBtn = document.getElementById('map-check-coverage');
        let resultSpan = document.getElementById('map-check-result');

        // If they don't exist, create them (fallback)
        if (!citySelect) {
            const controlsGrid = container.querySelector('.map-controls-grid') || container;
            citySelect = document.createElement('select');
            citySelect.id = 'map-city-select';
            const cityOption = document.createElement('option');
            cityOption.value = '';
            cityOption.textContent = '🏙️ Şəhər Seçin...';
            citySelect.appendChild(cityOption);
            controlsGrid.appendChild(citySelect);
        }
        
        if (!regionSelect) {
            const controlsGrid = container.querySelector('.map-controls-grid') || container;
            regionSelect = document.createElement('select');
            regionSelect.id = 'map-region-select';
            const regionOption = document.createElement('option');
            regionOption.value = '';
            regionOption.textContent = '📍 Rayon Seçin...';
            regionSelect.appendChild(regionOption);
            controlsGrid.appendChild(regionSelect);
        }
        
        if (!streetInput) {
            const controlsGrid = container.querySelector('.map-controls-grid') || container;
            const streetContainer = document.createElement('div');
            streetContainer.style.position = 'relative';
            
            streetInput = document.createElement('input');
            streetInput.type = 'text';
            streetInput.id = 'map-street-input';
            streetInput.placeholder = '🛣️ Küçə adını yazın...';
            streetInput.autocomplete = 'off';
            
            streetSuggestions = document.createElement('div');
            streetSuggestions.id = 'map-street-suggestions';
            streetSuggestions.className = 'street-suggestions';
            
            streetContainer.appendChild(streetInput);
            streetContainer.appendChild(streetSuggestions);
            controlsGrid.appendChild(streetContainer);
        }
        
        if (!numberInput) {
            const controlsGrid = container.querySelector('.map-controls-grid') || container;
            const numberContainer = document.createElement('div');
            numberContainer.style.position = 'relative';
            
            numberInput = document.createElement('input');
            numberInput.type = 'text';
            numberInput.id = 'map-number-input';
            numberInput.placeholder = '🏠 Ev nömrəsi';
            
            numberSuggestions = document.createElement('div');
            numberSuggestions.id = 'map-number-suggestions';
            numberSuggestions.className = 'number-suggestions';
            
            numberContainer.appendChild(numberInput);
            numberContainer.appendChild(numberSuggestions);
            controlsGrid.appendChild(numberContainer);
        }
        
        if (!checkBtn) {
            const controlsGrid = container.querySelector('.map-controls-grid') || container;
            checkBtn = document.createElement('button');
            checkBtn.id = 'map-check-coverage';
            checkBtn.textContent = '✨ Əhatəni Yoxla';
            controlsGrid.appendChild(checkBtn);
        }
        
        if (!resultSpan) {
            resultSpan = document.createElement('span');
            resultSpan.id = 'map-check-result';
            container.appendChild(resultSpan);
        }

        // populate cities
        citySelect.innerHTML = '';
        const cityEmpty = document.createElement('option'); 
        cityEmpty.value=''; 
        cityEmpty.text='Şəhər seçin'; 
        citySelect.appendChild(cityEmpty);
        
        // Get unique cities from posts
        const cities = [...new Set(posts.map(p => p.city).filter(city => city))];
        cities.sort();
        
        cities.forEach(city => {
            const opt = document.createElement('option'); 
            opt.value = city; 
            opt.text = city; 
            citySelect.appendChild(opt);
        });

        // populate regions initially (empty)
        regionSelect.innerHTML = '';
        const regionEmpty = document.createElement('option'); 
        regionEmpty.value=''; 
        regionEmpty.text='Rayon seçin'; 
        regionSelect.appendChild(regionEmpty);

        console.log('Populated city select with', cities.length, 'cities');

        function populateRegions(selectedCity){
            regionSelect.innerHTML = '';
            streetInput.value = '';
            streetSuggestions.style.display = 'none';
            const empty = document.createElement('option'); empty.value=''; empty.text='Rayon seçin'; regionSelect.appendChild(empty);
            
            if (!selectedCity) return;
            
            const cityRegions = posts.filter(p => p.city === selectedCity);
            cityRegions.forEach(p => {
                const opt = document.createElement('option'); 
                opt.value = p.id; 
                opt.text = p.title; 
                regionSelect.appendChild(opt);
            });
        }

        let streetSearchTimeout;
        function setupStreetAutocomplete() {
            streetInput.addEventListener('input', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                const term = this.value.trim();
                
                clearTimeout(streetSearchTimeout);
                
                if (!cityName || !regionId) {
                    streetSuggestions.style.display = 'none';
                    return;
                }
                
                streetSearchTimeout = setTimeout(() => {
                    const region = posts.find(p => String(p.id) === String(regionId));
                    if (!region) return;
                    
                    let matchingStreets;
                    if (term.length === 0) {
                        // Show all streets when no filter
                        matchingStreets = (region.addresses || [])
                            .map(addr => ({
                                label: addr.street,
                                value: addr.street,
                                numbers: addr.numbers || []
                            }));
                    } else {
                        // Filter streets when user types
                        matchingStreets = (region.addresses || [])
                            .filter(addr => addr.street.toLowerCase().includes(term.toLowerCase()))
                            .map(addr => ({
                                label: addr.street,
                                value: addr.street,
                                numbers: addr.numbers || []
                            }));
                    }
                    
                    displayStreetSuggestions(matchingStreets.slice(0, 10)); // Show up to 10 results
                }, 150);
            });
            
            streetInput.addEventListener('click', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                
                if (cityName && regionId) {
                    // Trigger input event to show all streets on click
                    this.dispatchEvent(new Event('input'));
                }
            });
            
            streetInput.addEventListener('blur', function() {
                // Hide suggestions after a delay to allow clicking
                setTimeout(() => {
                    streetSuggestions.style.display = 'none';
                }, 200);
            });
            
            streetInput.addEventListener('focus', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                
                if (cityName && regionId && streetSuggestions.children.length > 0) {
                    streetSuggestions.style.display = 'block';
                } else if (cityName && regionId) {
                    // Trigger input to show streets
                    this.dispatchEvent(new Event('input'));
                }
            });
        }
        
        let selectedStreetData = null;
        function setupNumberAutocomplete() {
            numberInput.addEventListener('input', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                const streetName = streetInput.value.trim();
                const term = this.value.trim();
                
                if (!cityName || !regionId || !streetName || !selectedStreetData) {
                    numberSuggestions.style.display = 'none';
                    return;
                }
                
                let matchingNumbers;
                if (term.length === 0) {
                    // Show all numbers when no filter
                    matchingNumbers = selectedStreetData.numbers || [];
                } else {
                    // Filter numbers when user types
                    matchingNumbers = (selectedStreetData.numbers || [])
                        .filter(num => num.toString().includes(term));
                }
                
                displayNumberSuggestions(matchingNumbers.slice(0, 20)); // Show up to 20 results
            });
            
            numberInput.addEventListener('click', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                const streetName = streetInput.value.trim();
                
                if (cityName && regionId && streetName && selectedStreetData) {
                    // Trigger input event to show all numbers on click
                    this.dispatchEvent(new Event('input'));
                }
            });
            
            numberInput.addEventListener('blur', function() {
                // Hide suggestions after a delay to allow clicking
                setTimeout(() => {
                    numberSuggestions.style.display = 'none';
                }, 200);
            });
            
            numberInput.addEventListener('focus', function() {
                const cityName = citySelect.value;
                const regionId = regionSelect.value;
                const streetName = streetInput.value.trim();
                
                if (cityName && regionId && streetName && selectedStreetData) {
                    // Trigger input to show numbers
                    this.dispatchEvent(new Event('input'));
                }
            });
        }
        
        function displayNumberSuggestions(numbers) {
            numberSuggestions.innerHTML = '';
            
            if (numbers.length === 0) {
                numberSuggestions.style.display = 'none';
                return;
            }
            
            numbers.forEach(number => {
                const item = document.createElement('div');
                item.className = 'number-suggestion-item';
                item.textContent = number;
                item.dataset.value = number;
                
                item.addEventListener('click', function() {
                    numberInput.value = this.dataset.value;
                    numberSuggestions.style.display = 'none';
                });
                
                numberSuggestions.appendChild(item);
            });
            
            numberSuggestions.style.display = 'block';
        }
        
        function displayStreetSuggestions(streets) {
            streetSuggestions.innerHTML = '';
            
            if (streets.length === 0) {
                streetSuggestions.style.display = 'none';
                selectedStreetData = null;
                return;
            }
            
            streets.forEach(street => {
                const item = document.createElement('div');
                item.className = 'street-suggestion-item';
                item.textContent = street.label;
                item.dataset.value = street.value;
                item.dataset.numbers = JSON.stringify(street.numbers);
                
                item.addEventListener('click', function() {
                    streetInput.value = this.dataset.value;
                    selectedStreetData = { numbers: JSON.parse(this.dataset.numbers) };
                    streetSuggestions.style.display = 'none';
                    
                    // Clear number input and reset suggestions
                    numberInput.value = '';
                    numberSuggestions.style.display = 'none';
                });
                
                streetSuggestions.appendChild(item);
            });
            
            streetSuggestions.style.display = 'block';
        }

        function populateStreets(postId){
            streetInput.value = '';
            streetSuggestions.style.display = 'none';
            streetInput.dataset.numbers = '';
            
            // Center map on selected region
            centerMapOnRegion(postId);
        }
        
        function centerMapOnRegion(postId) {
            if (!postId) return;
            
            // Find features belonging to this region
            const regionFeatures = features.getFeatures().filter(feature => {
                const props = feature.getProperties();
                return props._post_id && String(props._post_id) === String(postId);
            });
            
            if (regionFeatures.length > 0) {
                // Create extent from all features of this region
                let extent = ol.extent.createEmpty();
                regionFeatures.forEach(feature => {
                    ol.extent.extend(extent, feature.getGeometry().getExtent());
                });
                
                if (!ol.extent.isEmpty(extent)) {
                    // Fit map to region with padding
                    map.getView().fit(extent, { 
                        padding: [50, 50, 50, 50], 
                        duration: 800,
                        maxZoom: 16 
                    });
                }
            }
        }

        citySelect.addEventListener('change', function(){ populateRegions(this.value); });
        regionSelect.addEventListener('change', function(){ populateStreets(this.value); });
        
        // Setup street autocomplete
        setupStreetAutocomplete();
        
        // Setup number autocomplete
        setupNumberAutocomplete();

        // popup for feature info
        const popup = document.createElement('div');
        popup.id = 'coverage-popup';
        popup.style.display = 'none';
        popup.innerHTML = '<div id="coverage-popup-content"></div>';
        document.body.appendChild(popup);
        const overlay = new ol.Overlay({ element: popup, positioning: 'bottom-center', stopEvent: false });
        map.addOverlay(overlay);

        // show info on click
        map.on('singleclick', function(evt){
            const f = map.forEachFeatureAtPixel(evt.pixel, function(feature){ return feature; });
            if (f) {
                const props = f.getProperties();
                const geom = f.getGeometry();
                const coords = evt.coordinate;
                let html = '<strong>Əhatə Sahəsi</strong><br/>';
                if (props.title) html += '<div>' + escapeHtml(props.title) + '</div>';
                if (props._post_title) {
                    // Find the post to get city information
                    const post = posts.find(p => p.id == props._post_id);
                    const cityText = post && post.city ? post.city + ', ' : '';
                    html += '<div><em>' + escapeHtml(cityText) + escapeHtml(props._post_title) + '</em></div>';
                }
                if (props.radius_m) html += '<div>Radius: ' + Number(props.radius_m) + ' m</div>';
                
                // Add link to coverage post using actual post slug
                if (props._post_title) {
                    const post = posts.find(p => p.id == props._post_id);
                    const slug = post && post.slug ? post.slug : props._post_title.toLowerCase().replace(/\s+/g, '-');
                    html += '<div style="margin-top: 8px;"><a href="/ehate/' + encodeURIComponent(slug) + '" target="_blank" style="color: #0073aa; text-decoration: none;">Əhatə Təfərrüatlarını Göstər →</a></div>';
                }
                
                document.getElementById('coverage-popup-content').innerHTML = html;
                popup.style.display = 'block';
                overlay.setPosition(coords);
            } else {
                popup.style.display = 'none';
                overlay.setPosition(undefined);
            }
        });

        // Check coverage button
        checkBtn.addEventListener('click', function(){
            const cityName = citySelect.value;
            const regionId = regionSelect.value;
            const street = streetInput.value.trim();
            const number = numberInput.value.trim();
            
            if (!cityName) { 
                resultSpan.innerHTML = '<span>⚠️ Əvvəl bir şəhər seçin</span>'; 
                return; 
            }
            
            if (!regionId) { 
                resultSpan.innerHTML = '<span>⚠️ Zəhmət olmasa rayon seçin</span>'; 
                return; 
            }
            
            if (!street) {
                resultSpan.innerHTML = '<span>⚠️ Küçə adını daxil edin</span>'; 
                return; 
            }
            
            if (!number) {
                resultSpan.innerHTML = '<span>⚠️ Ev nömrəsini daxil edin</span>'; 
                return; 
            }
            
            const region = posts.find(x=> String(x.id) === String(regionId));
            if (!region) {
                resultSpan.innerHTML = '<span>❌ Rayon tapılmadı</span>';
                return;
            }
            
            resultSpan.innerHTML = '<span>🔍 Əhatə yoxlanılır...</span>';
            
            // Find the street in the region's addresses
            const streetData = (region.addresses || []).find(a => a.street.toLowerCase() === street.toLowerCase());
            
            if (!streetData) {
                // Get contact page URL
                const contactPageId = typeof MAP_COVERAGE_CONTACT_PAGE !== 'undefined' ? MAP_COVERAGE_CONTACT_PAGE : '';
                let contactLink = '';
                if (contactPageId) {
                    contactLink = ' <a href="/?page_id=' + contactPageId + '" target="_blank">Ətraflı məlumat üçün bizimlə əlaqə saxlayın</a>';
                }
                resultSpan.innerHTML = '<span>❌ Ünvanınız əhatə sahəmizdə deyil.' + contactLink + '</span>';
                return;
            }
            
            // Check if the house number is in our coverage list
            const isNumberCovered = streetData.numbers && streetData.numbers.includes(number);
            
            if (isNumberCovered) {
                resultSpan.innerHTML = '<span>🎉 Əla! ' + escapeHtml(cityName) + ', ' + escapeHtml(region.title) + ' ərazisindəki ünvanınız xidmətimizdən istifadə edə bilər</span>';
                if (region.slug) {
                    const postUrl = window.location.protocol + '//' + window.location.host + '/ehate/' + encodeURIComponent(region.slug);
                    resultSpan.innerHTML += '<a href="' + postUrl + '" target="_blank">📋 Ətraflı Məlumat</a>';
                }
            } else {
                // Get contact page URL
                const contactPageId = typeof MAP_COVERAGE_CONTACT_PAGE !== 'undefined' ? MAP_COVERAGE_CONTACT_PAGE : '';
                let contactLink = '';
                if (contactPageId) {
                    contactLink = ' <a href="/?page_id=' + contactPageId + '" target="_blank">Ətraflı məlumat üçün bizimlə əlaqə saxlayın</a>';
                }
                resultSpan.innerHTML = '<span>😔 Təəssüf ki, ' + escapeHtml(cityName) + ', ' + escapeHtml(region.title) + ' ərazisindəki ' + escapeHtml(street) + ' küçəsinin ' + escapeHtml(number) + ' nömrəli evi əhatə sahəmizə daxil deyil.' + contactLink + '</span>';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', init);

    // Global function for search-only form
    window.initializeSearchForm = function(redirectPage) {
        const searchCitySelect = document.getElementById('search-city-select');
        const searchRegionSelect = document.getElementById('search-region-select');
        const searchStreetInput = document.getElementById('search-street-input');
        const searchStreetSuggestions = document.getElementById('search-street-suggestions');
        const searchNumberInput = document.getElementById('search-number-input');
        const searchBtn = document.getElementById('search-coverage-btn');
        const searchResultMessage = document.getElementById('search-result-message');

        if (!searchCitySelect || !searchRegionSelect || !searchStreetInput || !searchNumberInput || !searchBtn) {
            console.error('Search form elements not found');
            return;
        }

        // Create suggestions container if not exists
        if (!searchStreetSuggestions) {
            const suggestions = document.createElement('div');
            suggestions.id = 'search-street-suggestions';
            suggestions.className = 'street-suggestions';
            searchStreetInput.parentNode.insertBefore(suggestions, searchStreetInput.nextSibling);
        }

        // Initialize with MAP_COVERAGE_DATA if available
        if (typeof MAP_COVERAGE_DATA !== 'undefined') {
            const posts = MAP_COVERAGE_DATA.posts || [];
            
            // Populate cities
            const cities = [...new Set(posts.map(p => p.city).filter(city => city))];
            cities.sort();
            
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.text = city;
                searchCitySelect.appendChild(opt);
            });

            // City change handler
            searchCitySelect.addEventListener('change', function() {
                searchRegionSelect.innerHTML = '<option value="">📍 Rayon Seçin...</option>';
                searchStreetInput.value = '';
                
                if (this.value) {
                    const cityRegions = posts.filter(p => p.city === this.value);
                    cityRegions.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        opt.text = p.title;
                        searchRegionSelect.appendChild(opt);
                    });
                }
            });

            // Region change handler
            searchRegionSelect.addEventListener('change', function() {
                searchStreetInput.value = '';
            });

            // Street autocomplete
            let streetSearchTimeout;
            searchStreetInput.addEventListener('input', function() {
                const cityName = searchCitySelect.value;
                const regionId = searchRegionSelect.value;
                const term = this.value.trim();
                
                clearTimeout(streetSearchTimeout);
                
                if (!cityName || !regionId) {
                    document.getElementById('search-street-suggestions').style.display = 'none';
                    return;
                }
                
                streetSearchTimeout = setTimeout(() => {
                    const region = posts.find(p => String(p.id) === String(regionId));
                    if (!region) return;
                    
                    let matchingStreets;
                    if (term.length === 0) {
                        // Show all streets when no filter
                        matchingStreets = (region.addresses || [])
                            .map(addr => ({
                                label: addr.street,
                                value: addr.street,
                                numbers: addr.numbers || []
                            }));
                    } else {
                        // Filter streets when user types
                        matchingStreets = (region.addresses || [])
                            .filter(addr => addr.street.toLowerCase().includes(term.toLowerCase()))
                            .map(addr => ({
                                label: addr.street,
                                value: addr.street,
                                numbers: addr.numbers || []
                            }));
                    }
                    
                    const suggestionsEl = document.getElementById('search-street-suggestions');
                    suggestionsEl.innerHTML = '';
                    
                    if (matchingStreets.length === 0) {
                        suggestionsEl.style.display = 'none';
                        return;
                    }
                    
                    matchingStreets.slice(0, 10).forEach(street => {
                        const item = document.createElement('div');
                        item.className = 'street-suggestion-item';
                        item.textContent = street.label;
                        item.addEventListener('click', function() {
                            searchStreetInput.value = street.value;
                            suggestionsEl.style.display = 'none';
                        });
                        suggestionsEl.appendChild(item);
                    });
                    
                    suggestionsEl.style.display = 'block';
                }, 150);
            });
            
            searchStreetInput.addEventListener('click', function() {
                const cityName = searchCitySelect.value;
                const regionId = searchRegionSelect.value;
                
                if (cityName && regionId) {
                    // Trigger input event to show all streets on click
                    this.dispatchEvent(new Event('input'));
                }
            });
            
            searchStreetInput.addEventListener('focus', function() {
                const cityName = searchCitySelect.value;
                const regionId = searchRegionSelect.value;
                
                if (cityName && regionId) {
                    // Trigger input to show streets
                    this.dispatchEvent(new Event('input'));
                }
            });

            // Search button handler
            searchBtn.addEventListener('click', function() {
                const cityName = searchCitySelect.value;
                const regionId = searchRegionSelect.value;
                const street = searchStreetInput.value.trim();
                const number = searchNumberInput.value.trim();
                
                // Validation
                if (!cityName || !regionId || !street || !number) {
                    showSearchMessage('Zəhmət olmasa bütün sahələri doldurun.', 'error');
                    return;
                }
                
                // Find region
                const region = posts.find(p => String(p.id) === String(regionId));
                if (!region) {
                    showSearchMessage('Rayon tapılmadı.', 'error');
                    return;
                }
                
                // Check coverage
                const streetData = (region.addresses || []).find(a => a.street.toLowerCase() === street.toLowerCase());
                
                if (!streetData || !streetData.numbers || !streetData.numbers.includes(number)) {
                    // Not covered - show error with contact link
                    const contactPageId = typeof MAP_COVERAGE_CONTACT_PAGE !== 'undefined' ? MAP_COVERAGE_CONTACT_PAGE : '';
                    let contactLink = '';
                    if (contactPageId) {
                        contactLink = ' <a href="/?page_id=' + contactPageId + '" target="_blank">Ətraflı məlumat üçün bizimlə əlaqə saxlayın</a>';
                    }
                    showSearchMessage('Ünvanınız əhatə sahəmizdə deyil.' + contactLink, 'error');
                    return;
                }
                
                // Covered - redirect to coverage page
                if (region.slug) {
                    const targetUrl = redirectPage || '/ehate/' + encodeURIComponent(region.slug);
                    window.location.href = targetUrl;
                } else {
                    showSearchMessage('Əla! Ünvanınız əhatə sahəmizdədir.', 'success');
                }
            });
        }

        function showSearchMessage(message, type) {
            if (searchResultMessage) {
                searchResultMessage.innerHTML = message;
                searchResultMessage.className = type;
                searchResultMessage.style.display = 'block';
                
                if (type === 'success') {
                    setTimeout(() => {
                        searchResultMessage.style.display = 'none';
                    }, 5000);
                }
            }
        }
    };

    // Simplified search form - only city and search
    window.initializeSimpleSearchForm = function(redirectPage) {
        const searchCitySelect = document.getElementById('search-city-select');
        const searchBtn = document.getElementById('search-coverage-btn');
        const searchResultMessage = document.getElementById('search-result-message');

        if (!searchCitySelect || !searchBtn) {
            console.error('Simple search form elements not found');
            return;
        }

        // Initialize with MAP_COVERAGE_DATA if available
        if (typeof MAP_COVERAGE_DATA !== 'undefined') {
            const posts = MAP_COVERAGE_DATA.posts || [];
            
            // Populate cities
            const cities = [...new Set(posts.map(p => p.city).filter(city => city))];
            cities.sort();
            
            cities.forEach(city => {
                const opt = document.createElement('option');
                opt.value = city;
                opt.text = city;
                searchCitySelect.appendChild(opt);
            });

            // Search button handler
            searchBtn.addEventListener('click', function() {
                const cityName = searchCitySelect.value;
                
                if (!cityName) {
                    showSimpleSearchMessage('Zəhmət olmasa bir şəhər seçin.', 'error');
                    return;
                }
                
                // Find first region in this city
                const cityRegions = posts.filter(p => p.city === cityName);
                
                if (cityRegions.length === 0) {
                    const contactPageId = typeof MAP_COVERAGE_CONTACT_PAGE !== 'undefined' ? MAP_COVERAGE_CONTACT_PAGE : '';
                    let contactLink = '';
                    if (contactPageId) {
                        contactLink = ' <a href="/?page_id=' + contactPageId + '" target="_blank">Ətraflı məlumat üçün bizimlə əlaqə saxlayın</a>';
                    }
                    showSimpleSearchMessage('Bu şəhərdə əhatə sahəmiz yoxdur.' + contactLink, 'error');
                    return;
                }
                
                // Redirect to coverage page - if redirect_page specified, use it, otherwise go to first region
                let targetUrl;
                if (redirectPage) {
                    targetUrl = redirectPage;
                } else if (cityRegions[0].slug) {
                    targetUrl = '/ehate/' + encodeURIComponent(cityRegions[0].slug);
                } else {
                    targetUrl = '/ehate/';
                }
                
                showSimpleSearchMessage('Yönləndirilir...', 'success');
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 500);
            });
        } else {
            console.error('MAP_COVERAGE_DATA not found');
        }

        function showSimpleSearchMessage(message, type) {
            if (searchResultMessage) {
                searchResultMessage.innerHTML = message;
                searchResultMessage.className = type;
                searchResultMessage.style.display = 'block';
                
                if (type === 'success') {
                    setTimeout(() => {
                        searchResultMessage.style.display = 'none';
                    }, 3000);
                }
            }
        }
    };
})();
