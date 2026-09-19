(() => {
    'use strict';

    const locations = Array.isArray(window.ParkNexaLocations)
        ? window.ParkNexaLocations
        : [];

    const apiKey = String(window.ParkNexaGoogleMapsKey || '').trim();

    const mapElement = document.getElementById('googleParkingMap');
    const loading = document.getElementById('mapLoading');
    const errorBox = document.getElementById('mapError');

    if (!mapElement) return;

    function showError(message) {
        if (loading) loading.hidden = true;

        if (errorBox) {
            errorBox.hidden = false;

            const span = errorBox.querySelector('span');

            if (span && message) {
                span.textContent = message;
            }
        }
    }

    function showLoading(isLoading) {
        if (loading) {
            loading.hidden = !isLoading;
        }
    }

    if (
        !apiKey ||
        apiKey === 'YOUR_GOOGLE_MAPS_API_KEY'
    ) {
        showError(
            'Add your Google Maps API key in config/maps.php.'
        );
        return;
    }

    /*
     * Google recommends the async Maps JavaScript loader and
     * importLibrary() for modular loading.
     */
    window.initParkNexaMap = async function initParkNexaMap() {

        try {

            const [
                { Map, InfoWindow },
                { Geocoder },
                { AdvancedMarkerElement }
            ] = await Promise.all([
                google.maps.importLibrary('maps'),
                google.maps.importLibrary('geocoding'),
                google.maps.importLibrary('marker')
            ]);

            if (!locations.length) {
                showError('No parking locations are available.');
                return;
            }

            showLoading(true);

            const fallbackCenter = {
                lat: 28.6139,
                lng: 77.2090
            };

            const map = new Map(mapElement, {
                center: fallbackCenter,
                zoom: 11,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: true,
                clickableIcons: false,
                gestureHandling: 'greedy',
                mapId: 'PARKNEXA_MAP'
            });

            const bounds = new google.maps.LatLngBounds();
            const infoWindow = new InfoWindow();
            const geocoder = new Geocoder();

            let completed = 0;

            for (const location of locations) {

                const address = [
                    location.address,
                    location.city,
                    'India'
                ].filter(Boolean).join(', ');

                try {

                    const response = await geocoder.geocode({
                        address
                    });

                    if (!response.results || !response.results.length) {
                        completed++;
                        continue;
                    }

                    const position =
                        response.results[0].geometry.location;

                    bounds.extend(position);

                    const marker = document.createElement('div');
                    marker.className = 'parknexa-map-price-marker';
                    marker.textContent =
                        '₹' + Math.round(Number(location.rate));

                    const advancedMarker =
                        new AdvancedMarkerElement({
                            map,
                            position,
                            title: location.name,
                            content: marker
                        });

                    advancedMarker.addListener('click', () => {

                        const content = `
                            <div class="parknexa-map-popup">
                                <strong>${escapeHtml(location.name)}</strong>
                                <span>${escapeHtml(location.address)}</span>

                                <div class="map-popup-row">
                                    <span>${location.available} slots available</span>
                                    <b>₹${Math.round(Number(location.rate))}/hr</b>
                                </div>

                                <a
                                    href="${escapeHtml(location.url)}"
                                    class="map-popup-button"
                                >
                                    View Slots →
                                </a>
                            </div>
                        `;

                        infoWindow.setContent(content);
                        infoWindow.open({
                            map,
                            anchor: advancedMarker
                        });
                    });

                    completed++;

                } catch (geocodeError) {

                    console.warn(
                        'ParkNexa geocoding failed:',
                        location.name,
                        geocodeError
                    );

                    completed++;
                }
            }

            if (completed > 0 && !bounds.isEmpty()) {
                map.fitBounds(bounds);

                google.maps.event.addListenerOnce(
                    map,
                    'bounds_changed',
                    () => {
                        if (map.getZoom() > 14) {
                            map.setZoom(14);
                        }
                    }
                );
            }

            showLoading(false);

        } catch (error) {

            console.error('Google Maps initialization error:', error);

            showError(
                'Google Maps could not be loaded. Check the API key and enabled APIs.'
            );
        }
    };


    function loadGoogleMaps() {

        if (document.getElementById('parknexa-google-maps-loader')) {
            return;
        }

        const script = document.createElement('script');

        script.id = 'parknexa-google-maps-loader';
        script.async = true;
        script.defer = true;

        script.src =
            'https://maps.googleapis.com/maps/api/js' +
            '?key=' + encodeURIComponent(apiKey) +
            '&loading=async' +
            '&libraries=maps,marker,geocoding' +
            '&callback=initParkNexaMap';

        script.onerror = () => {
            showError('Google Maps script could not be loaded.');
        };

        document.head.appendChild(script);
    }


    function escapeHtml(value) {

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    loadGoogleMaps();


    /* ---------------------------------------------
       List / Map view toggle
       --------------------------------------------- */

    document.addEventListener('click', (event) => {

        const button = event.target.closest('.view-toggle');

        if (!button) return;

        const view = button.dataset.view;

        document
            .querySelectorAll('.view-toggle')
            .forEach((item) => {
                const active = item === button;

                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

        const listings = document.querySelector('.find-listings');
        const mapPanel = document.getElementById('mapPanel');

        if (!listings || !mapPanel) return;

        if (view === 'map') {
            listings.style.display = 'none';
            mapPanel.style.display = 'block';
            return;
        }

        listings.style.display = '';
        mapPanel.style.display = '';

    });

})();
