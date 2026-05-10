document.addEventListener('DOMContentLoaded', () => {
  const mapElement = document.querySelector('[data-aziza-map]');
  const statusElement = document.querySelector('[data-aziza-map-status]');
  const citySelect = document.querySelector('[data-aziza-city-select]');
  const citySearch = document.querySelector('[data-aziza-city-search]');

  if (!mapElement || typeof L === 'undefined') return;

  const azizaLogo = mapElement.dataset.azizaLogo || '';
  const mgLogo = mapElement.dataset.mgLogo || '';
  const monoprixLogo = mapElement.dataset.monoprixLogo || '';
  const carrefourLogo = mapElement.dataset.carrefourLogo || '';
  const inventoryByBrand = (() => {
    try {
      const parsed = JSON.parse(mapElement.dataset.storeInventory || '{}');
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (error) {
      return {};
    }
  })();
  const searchRadius = 25000;
  const liveApiTimeout = 10000;
  const defaultCity = 'tunis';
  const arabicAziza = '\\u0639\\u0632\\u064a\\u0632\\u0629';
  const cityCenters = {
    tunis: { lat: 36.8065, lng: 10.1815, label: 'Tunis' },
    ariana: { lat: 36.8625, lng: 10.1956, label: 'Ariana' },
    manouba: { lat: 36.8093, lng: 10.0863, label: 'Manouba' },
    ben_arous: { lat: 36.7531, lng: 10.2189, label: 'Ben Arous' },
    sousse: { lat: 35.8256, lng: 10.6084, label: 'Sousse' },
    sfax: { lat: 34.7406, lng: 10.7603, label: 'Sfax' }
  };

  const savedStoresByCity = {};

  const apiEndpoints = [
    'https://overpass-api.de/api/interpreter',
    'https://overpass.kumi.systems/api/interpreter'
  ];

  const markerLayer = L.layerGroup();
  let userLocationMarker = null;
  const prettyTileConfig = {
    url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
  };

  const setStatus = (message) => {
    if (statusElement) statusElement.textContent = message;
  };

  const map = L.map(mapElement, {
    scrollWheelZoom: false
  }).setView([cityCenters[defaultCity].lat, cityCenters[defaultCity].lng], 13);

  markerLayer.addTo(map);

  L.tileLayer(prettyTileConfig.url, {
    attribution: prettyTileConfig.attribution,
    subdomains: 'abcd',
    maxZoom: 20
  }).addTo(map);

  const userIcon = L.divIcon({
    className: 'foovia-user-map-pin',
    html: '<span></span>',
    iconSize: [22, 22],
    iconAnchor: [11, 11]
  });

  const brandDefinitions = {
    aziza: { label: 'AZ', logo: azizaLogo, className: 'foovia-brand-aziza', tooltipClass: 'foovia-tooltip-aziza' },
    mg: { label: 'MG', logo: mgLogo, className: 'foovia-brand-mg', tooltipClass: 'foovia-tooltip-mg' },
    monoprix: { label: 'MP', logo: monoprixLogo, className: 'foovia-brand-monoprix', tooltipClass: 'foovia-tooltip-monoprix' },
    carrefour: { label: 'CR', logo: carrefourLogo, className: 'foovia-brand-carrefour', tooltipClass: 'foovia-tooltip-carrefour' },
    market: { label: 'MK', logo: '', className: 'foovia-brand-market', tooltipClass: 'foovia-tooltip-market' }
  };

  const hasDatabaseImage = (brandKey) => Boolean(brandDefinitions[brandKey]?.logo);

  const isAzizaName = (name) => new RegExp(`aziza|${arabicAziza}`, 'i').test(String(name || ''));
  const isMgName = (name) => /(^|\s)mg(\s|$)|magasin general/i.test(String(name || ''));
  const isMonoprixName = (name) => /monoprix/i.test(String(name || ''));
  const isCarrefourName = (name) => /carrefour/i.test(String(name || ''));

  const detectBrand = (name) => {
    if (isAzizaName(name)) return 'aziza';
    if (isMgName(name)) return 'mg';
    if (isMonoprixName(name)) return 'monoprix';
    if (isCarrefourName(name)) return 'carrefour';
    return 'market';
  };

  const allSavedStores = Object.values(savedStoresByCity)
    .flat()
    .filter((store, index, stores) => stores.findIndex((candidate) =>
      candidate.name === store.name
      && candidate.lat === store.lat
      && candidate.lng === store.lng
    ) === index)
    .map((store) => ({
      ...store,
      brand: detectBrand(store.name)
    }));

  const createBrandIcon = (brandKey) => {
    const brand = brandDefinitions[brandKey] || brandDefinitions.market;
    return L.divIcon({
      className: 'foovia-market-map-pin',
      html: `<div class="foovia-market-pin-body ${brand.className}">${brand.logo ? `<img src="${brand.logo}" alt="${brandKey}">` : `<span>${brand.label}</span>`}</div>`,
      iconSize: [46, 54],
      iconAnchor: [23, 54],
      popupAnchor: [0, -54]
    });
  };

  const displayStoreName = (name) => {
    if (isAzizaName(name)) return 'Aziza';
    return String(name || 'Market');
  };

  const stockListHtml = (brandKey) => {
    const items = Array.isArray(inventoryByBrand[brandKey]) ? inventoryByBrand[brandKey] : [];
    if (!items.length) {
      return '<div class="foovia-store-stock-empty">No stock data linked yet.</div>';
    }

    return `
      <div class="foovia-store-stock-list">
        ${items.map((item) => `
          <div class="foovia-store-stock-row">
            <span>${item.name}</span>
            <b>${item.quantity}</b>
          </div>
        `).join('')}
      </div>
    `;
  };

  const tooltipHtml = (name, brandKey = 'market') => `
    <div class="foovia-aziza-tooltip">
      ${brandDefinitions[brandKey]?.logo ? `<img src="${brandDefinitions[brandKey].logo}" alt="${name}">` : `<span>${brandDefinitions[brandKey]?.label || 'MK'}</span>`}
      <div class="foovia-store-tooltip-copy">
        <strong>${displayStoreName(name)}</strong>
        <small>In stock now</small>
        ${stockListHtml(brandKey)}
      </div>
    </div>
  `;

  const normalizeStores = (elements) => {
    const seen = new Set();
    return elements
      .map((item) => {
        const lat = item.lat || item.center?.lat;
        const lng = item.lon || item.center?.lon;
        if (!lat || !lng) return null;

        const rawName = item.tags?.name || item.tags?.brand || item.tags?.operator || 'Market';
        const brand = detectBrand(rawName);
        if (!hasDatabaseImage(brand)) return null;

        const name = displayStoreName(rawName);
        const key = `${Number(lat).toFixed(5)}:${Number(lng).toFixed(5)}:${name.toLowerCase()}`;
        if (seen.has(key)) return null;
        seen.add(key);

        return {
          lat,
          lng,
          name,
          brand
        };
      })
      .filter(Boolean);
  };

  const closestCityKey = (center) => Object.entries(cityCenters)
    .map(([key, city]) => ({
      key,
      distance: Math.hypot(center.lat - city.lat, center.lng - city.lng)
    }))
    .sort((a, b) => a.distance - b.distance)[0]?.key || defaultCity;

  const addMarketMarkers = (stores, center, statusPrefix) => {
    markerLayer.clearLayers();
    userLocationMarker = L.marker([center.lat, center.lng], { icon: userIcon }).addTo(markerLayer).bindTooltip('Your location');

    const bounds = L.latLngBounds([[center.lat, center.lng]]);
    stores.forEach((store) => {
      const brand = store.brand || detectBrand(store.name);
      bounds.extend([store.lat, store.lng]);
      L.marker([store.lat, store.lng], { icon: createBrandIcon(brand) })
        .addTo(markerLayer)
        .bindPopup(tooltipHtml(store.name, brand), {
          autoPan: true,
          closeButton: false,
          className: `foovia-aziza-tooltip-wrap ${brandDefinitions[brand]?.tooltipClass || 'foovia-tooltip-market'}`,
          offset: [0, -44],
          maxWidth: 260,
          minWidth: 220
        });
    });

    map.fitBounds(bounds, { padding: [36, 36], maxZoom: 15 });
    setStatus('Markets loaded.');
  };

  const addNationwideMarkers = (stores, statusPrefix, userCenter = null, focusOnUser = false) => {
    markerLayer.clearLayers();

    const bounds = L.latLngBounds([]);
    if (userCenter) {
      userLocationMarker = L.marker([userCenter.lat, userCenter.lng], { icon: userIcon }).addTo(markerLayer).bindTooltip('Your location');
      bounds.extend([userCenter.lat, userCenter.lng]);
    }
    stores.forEach((store) => {
      const brand = store.brand || detectBrand(store.name);
      bounds.extend([store.lat, store.lng]);
      L.marker([store.lat, store.lng], { icon: createBrandIcon(brand) })
        .addTo(markerLayer)
        .bindPopup(tooltipHtml(store.name, brand), {
          autoPan: true,
          closeButton: false,
          className: `foovia-aziza-tooltip-wrap ${brandDefinitions[brand]?.tooltipClass || 'foovia-tooltip-market'}`,
          offset: [0, -44],
          maxWidth: 260,
          minWidth: 220
        });
    });

    if (userCenter && focusOnUser) {
      map.setView([userCenter.lat, userCenter.lng], 11);
    } else if (bounds.isValid()) {
      map.fitBounds(bounds, { padding: [42, 42], maxZoom: 8 });
    }

    setStatus('Markets loaded.');
  };

  const showMapCenterOnly = (center, message) => {
    markerLayer.clearLayers();
    userLocationMarker = L.marker([center.lat, center.lng], { icon: userIcon })
      .addTo(markerLayer)
      .bindTooltip('Search center');
    map.setView([center.lat, center.lng], 13);
    setStatus(message);
  };

  const buildQuery = (center) => `
    [out:json][timeout:25];
    (
      node(around:${searchRadius},${center.lat},${center.lng})["shop"~"supermarket|convenience|grocery|greengrocer|department_store",i];
      way(around:${searchRadius},${center.lat},${center.lng})["shop"~"supermarket|convenience|grocery|greengrocer|department_store",i];
      relation(around:${searchRadius},${center.lat},${center.lng})["shop"~"supermarket|convenience|grocery|greengrocer|department_store",i];
    );
    out center tags;
  `;

  const fetchMarketStores = async (center) => {
    const query = buildQuery(center);
    let lastError = null;

    for (const endpoint of apiEndpoints) {
      try {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), liveApiTimeout);
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
          body: query,
          signal: controller.signal
        });
        window.clearTimeout(timeout);
        if (!response.ok) throw new Error(`Map API failed: ${response.status}`);
        const data = await response.json();
        return normalizeStores(data.elements || []);
      } catch (error) {
        lastError = error;
      }
    }

    throw lastError || new Error('Map API failed');
  };

  const loadStores = async (center, cityKey = closestCityKey(center), label = 'near this area') => {
    map.setView([center.lat, center.lng], 13);
    const savedStores = savedStoresByCity[cityKey] || [];
    if (savedStores.length > 0) {
      addMarketMarkers(savedStores, center, `Saved markets ${label}`);
    } else {
      showMapCenterOnly(center, 'Loading live markets...');
    }

    try {
      const stores = await fetchMarketStores(center);
      if (stores.length > 0) {
        addMarketMarkers(stores, center, `Live markets ${label}`);
        return;
      }

      showMapCenterOnly(center, 'No Foovia markets with database images found nearby.');
    } catch (error) {
      if (savedStores.length > 0) {
        setStatus('Saved markets loaded.');
      } else {
        showMapCenterOnly(center, 'Live market API unavailable. Try another city.');
      }
    }
  };

  const loadSelectedCity = () => {
    const selected = citySelect?.value || 'auto';
    if (selected === 'all') {
      if (allSavedStores.length > 0) {
        addNationwideMarkers(allSavedStores, 'All saved markets');
      } else {
        markerLayer.clearLayers();
        map.setView([cityCenters[defaultCity].lat, cityCenters[defaultCity].lng], 7);
        setStatus('Choose a city or use your location to load live markets.');
      }
      return;
    }
    if (selected !== 'auto') {
      const city = cityCenters[selected] || cityCenters[defaultCity];
      loadStores(city, selected, `around ${city.label}`);
      return;
    }

    loadBrowserLocation();
  };

  const loadBrowserLocation = () => {
    if (!('geolocation' in navigator)) {
      const city = cityCenters[defaultCity];
      setStatus('Markets loaded.');
      loadStores(city, 'tunis', 'around Tunis');
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const center = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        };
        if ((citySelect?.value || 'auto') === 'all') {
          if (allSavedStores.length > 0) {
            addNationwideMarkers(allSavedStores, 'All saved markets', center, false);
            setStatus('Markets loaded.');
          } else {
            loadStores(center, closestCityKey(center), 'near your browser location');
          }
          return;
        }
        loadStores(center, closestCityKey(center), 'near your browser location');
      },
      () => {
        const city = cityCenters.tunis;
        setStatus('Markets loaded.');
        loadStores(city, 'tunis', 'around Tunis');
      },
      { enableHighAccuracy: true, timeout: 9000 }
    );
  };

  if (citySearch) citySearch.addEventListener('click', loadSelectedCity);
  if (citySelect) citySelect.addEventListener('change', loadSelectedCity);

  if (citySelect) {
    citySelect.value = 'all';
  }
  loadBrowserLocation();
});
