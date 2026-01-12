<?php
/**
 * Dashboard Principal LogiPlus
 */
require_once 'queries.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogiPlus - Dashboard PostGIS</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- JS Libraries -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        #map {
            height: 450px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }
        .tab-btn {
            transition: all 0.3s;
            cursor: pointer;
        }
        .tab-btn:hover {
            background-color: #f3f4f6;
        }
        .tab-btn.active {
            background-color: #3B82F6;
            color: white;
            border-radius: 8px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-delivered { background: #D1FAE5; color: #065F46; }
        .status-shipped { background: #DBEAFE; color: #1E40AF; }
        .status-confirmed { background: #FEF3C7; color: #92400E; }
        .status-created { background: #F3F4F6; color: #374151; }
        .status-cancelled { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white shadow-lg">
        <div class="container mx-auto px-6 py-6">
            <h1 class="text-3xl font-bold">📊 LOGIPLUS</h1>
            <p class="text-blue-100 mt-1">Dashboard Complet - Analytics & Données Spatiales</p>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white shadow-md sticky top-0 z-10">
        <div class="container mx-auto px-6">
            <div class="flex gap-2 py-2">
                <button onclick="showTab('proximite')" class="tab-btn px-6 py-3 font-medium active" data-tab="proximite">
                    📍 Analyse Proximité
                </button>
                <button onclick="showTab('transporteurs')" class="tab-btn px-6 py-3 font-medium" data-tab="transporteurs">
                    🚚 Transporteurs
                </button>
                <button onclick="showTab('analytics')" class="tab-btn px-6 py-3 font-medium" data-tab="analytics">
                    📊 Analytics
                </button>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow-md p-5 border-l-4 border-blue-600">
                <p class="text-gray-500 text-sm">Entrepôts</p>
                <p class="text-3xl font-bold text-blue-600 mt-1"><?= $kpi['total_warehouses'] ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5 border-l-4 border-orange-600">
                <p class="text-gray-500 text-sm">Commandes Actives</p>
                <p class="text-3xl font-bold text-orange-600 mt-1"><?= $kpi['active_orders'] ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5 border-l-4 border-green-600">
                <p class="text-gray-500 text-sm">Livrées</p>
                <p class="text-3xl font-bold text-green-600 mt-1"><?= $kpi['delivered_orders'] ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-5 border-l-4 border-purple-600">
                <p class="text-gray-500 text-sm">Produits</p>
                <p class="text-3xl font-bold text-purple-600 mt-1"><?= $kpi['total_products'] ?></p>
            </div>
        </div>

        <!-- TAB 1: Analyse Proximité -->
        <div id="tab-proximite" class="tab-content active">
            
            <!-- Carte -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">🗺️ Carte Interactive</h2>
                <div id="map"></div>
                <p class="text-sm text-gray-500 mt-3">💡 Lignes KNN reliant commandes → entrepôt le plus proche (opérateur &lt;-&gt;)</p>
            </div>

            <!-- Bandes de distance -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">📊 Distribution par Bandes de Distance</h2>
                <canvas id="bandsChart" height="80"></canvas>
            </div>

        </div>

        <!-- TAB 2: Transporteurs -->
        <div id="tab-transporteurs" class="tab-content">
            
            <!-- Performance Transporteurs -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-2xl font-bold mb-4">🚚 Performance des Transporteurs</h2>
                
                <?php if (!empty($carriers)): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach($carriers as $carrier): ?>
                    <div class="border-2 border-gray-200 rounded-lg p-5 hover:border-blue-400 transition">
                        <h3 class="text-lg font-bold mb-3"><?= htmlspecialchars($carrier['name']) ?></h3>
                        
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Expéditions totales</p>
                                <p class="text-2xl font-bold text-blue-600"><?= $carrier['shipment_count'] ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-600">Transit moyen</p>
                                <p class="text-xl font-bold text-purple-600"><?= $carrier['avg_transit_days'] ?? 0 ?> jours</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-600">Taux de ponctualité</p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: <?= $carrier['on_time_rate'] ?? 0 ?>%"></div>
                                    </div>
                                    <span class="text-lg font-bold text-green-600"><?= $carrier['on_time_rate'] ?? 0 ?>%</span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2 text-xs pt-2 border-t">
                                <div class="text-center">
                                    <p class="text-gray-500">Livrées</p>
                                    <p class="font-bold text-green-600"><?= $carrier['delivered_count'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-gray-500">En transit</p>
                                    <p class="font-bold text-blue-600"><?= $carrier['in_transit_count'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-gray-500">Exceptions</p>
                                    <p class="font-bold text-red-600"><?= $carrier['exception_count'] ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-center text-gray-500 py-8">Aucune donnée de transporteur disponible</p>
                <?php endif; ?>
            </div>

            <!-- Détails des Commandes -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">📦 Détails des Commandes</h2>
                
                <?php if (!empty($orders)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-3 text-left">N° Commande</th>
                                <th class="px-3 py-3 text-left">Client</th>
                                <th class="px-3 py-3 text-left">Date</th>
                                <th class="px-3 py-3 text-left">Destination</th>
                                <th class="px-3 py-3 text-left">Entrepôt</th>
                                <th class="px-3 py-3 text-center">Distance</th>
                                <th class="px-3 py-3 text-center">Items</th>
                                <th class="px-3 py-3 text-right">Valeur</th>
                                <th class="px-3 py-3 text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-3 py-3 font-medium"><?= htmlspecialchars($order['order_no']) ?></td>
                                <td class="px-3 py-3"><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td class="px-3 py-3"><?= date('d/m/Y', strtotime($order['order_date'])) ?></td>
                                <td class="px-3 py-3">
                                    <?= htmlspecialchars($order['dest_city']) ?>, 
                                    <?= htmlspecialchars($order['dest_country']) ?>
                                </td>
                                <td class="px-3 py-3"><?= htmlspecialchars($order['nearest_warehouse'] ?? 'N/A') ?></td>
                                <td class="px-3 py-3 text-center font-bold text-blue-600"><?= $order['distance_km'] ?? 0 ?> km</td>
                                <td class="px-3 py-3 text-center"><?= $order['total_items'] ?></td>
                                <td class="px-3 py-3 text-right font-bold"><?= number_format($order['order_value'], 2) ?>€</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center text-gray-500 py-8">Aucune commande trouvée</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- TAB 3: Analytics -->
        <div id="tab-analytics" class="tab-content">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                
                <!-- Q8: Top Produits par CA -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">📊 Top CA (30j)</h2>
                    
                    <?php if (!empty($q8)): ?>
                    <div class="space-y-2">
                        <?php foreach($q8 as $index => $product): ?>
                        <div class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-600 text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-sm"><?= htmlspecialchars($product['name']) ?></p>
                                <p class="text-xs text-gray-600">
                                    <?= htmlspecialchars($product['sku'] ?? 'N/A') ?> • <?= $product['total_qty'] ?> unités
                                </p>
                            </div>
                            <p class="text-lg font-bold text-indigo-600"><?= number_format($product['revenue'], 0) ?>€</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-gray-500 py-8">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>

                <!-- Q1: Top Produits par Quantité -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4">📦 Top Quantité (30j)</h2>
                    <?php if (!empty($q1)): ?>
                    <canvas id="q1Chart"></canvas>
                    <?php else: ?>
                    <p class="text-center text-gray-500 py-8">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

                <!-- Q4: Valeur par Pays -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-bold mb-4">🌍 Valeur par Pays (30j)</h3>
                    
                    <?php if (!empty($q4)): ?>
                    <div class="space-y-4">
                        <?php 
                        $maxValue = max(array_column($q4, 'total_sales'));
                        foreach($q4 as $country): 
                            $percentage = ($country['total_sales'] / $maxValue) * 100;
                        ?>
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-medium"><?= htmlspecialchars($country['dest_country']) ?></span>
                                <span class="font-bold text-blue-600"><?= number_format($country['total_sales'], 0) ?>€</span>
                            </div>
                            <div class="flex-1 bg-gray-200 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-gray-500 py-8">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>

                <!-- Q5: Top Commandes -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-xl font-bold mb-4">💰 Top Commandes par Valeur</h3>
                    
                    <?php if (!empty($q5)): ?>
                    <div class="space-y-2">
                        <?php foreach($q5 as $order): ?>
                        <div class="flex justify-between items-center p-3 border rounded-lg hover:bg-gray-50">
                            <div>
                                <p class="font-bold text-sm"><?= htmlspecialchars($order['order_no']) ?></p>
                                <p class="text-xs text-gray-600"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></p>
                            </div>
                            <p class="text-lg font-bold text-green-600"><?= number_format($order['total_value'], 2) ?>€</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-center text-gray-500 py-8">Aucune donnée disponible</p>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

    <footer class="bg-gray-800 text-white mt-12 py-6">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-400 text-sm">LogiPlus - Dashboard PostGIS Complet</p>
        </div>
    </footer>

    <script>
        // Données PHP → JavaScript
        const warehouses = <?= json_encode($warehouses) ?>;
        const orders_map = <?= json_encode($orders_map) ?>;
        const bands = <?= json_encode($bands) ?>;
        const q1 = <?= json_encode($q1) ?>;

        // Fonction pour changer d'onglet
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            document.querySelector('[data-tab="' + tabName + '"]').classList.add('active');
        }

        // Initialisation de la carte Leaflet
        if (typeof L !== 'undefined' && warehouses.length > 0) {
            const map = L.map('map').setView([46.5, 2.5], 5);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Ajouter les entrepôts
            warehouses.forEach(wh => {
                L.circleMarker([parseFloat(wh.lat), parseFloat(wh.lon)], {
                    radius: 10,
                    fillColor: '#3B82F6',
                    color: '#1E40AF',
                    weight: 2,
                    fillOpacity: 0.8
                }).addTo(map).bindPopup(`<strong>${wh.name}</strong><br>${wh.city}, ${wh.country}`);
            });

            // Ajouter les commandes avec lignes KNN
            orders_map.forEach(order => {
                L.circleMarker([parseFloat(order.lat), parseFloat(order.lon)], {
                    radius: 5,
                    fillColor: '#F97316',
                    color: '#EA580C',
                    weight: 1,
                    fillOpacity: 0.7
                }).addTo(map);

                // Trouver l'entrepôt le plus proche
                let minDist = Infinity;
                let nearest = null;
                
                warehouses.forEach(wh => {
                    const dist = Math.sqrt(
                        Math.pow(order.lat - wh.lat, 2) + 
                        Math.pow(order.lon - wh.lon, 2)
                    );
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = wh;
                    }
                });

                // Tracer la ligne KNN
                if (nearest) {
                    L.polyline([
                        [parseFloat(order.lat), parseFloat(order.lon)],
                        [parseFloat(nearest.lat), parseFloat(nearest.lon)]
                    ], {
                        color: '#9CA3AF',
                        weight: 1,
                        opacity: 0.5,
                        dashArray: '5, 5'
                    }).addTo(map);
                }
            });
        }

        // Graphique bandes de distance
        if (typeof Chart !== 'undefined' && bands.length > 0) {
            new Chart(document.getElementById('bandsChart'), {
                type: 'bar',
                data: {
                    labels: bands.map(b => b.band),
                    datasets: [{
                        label: 'Nombre de commandes',
                        data: bands.map(b => b.count),
                        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Graphique Q1 (quantités)
        if (typeof Chart !== 'undefined' && q1.length > 0) {
            new Chart(document.getElementById('q1Chart'), {
                type: 'bar',
                data: {
                    labels: q1.map(p => p.name),
                    datasets: [{
                        label: 'Quantité',
                        data: q1.map(p => p.total_qty),
                        backgroundColor: '#8B5CF6'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        console.log('✅ Dashboard chargé avec succès');
        console.log('📊 Données:', q1.length, 'produits,', bands.length, 'bandes de distance');
    </script>
</body>
</html>