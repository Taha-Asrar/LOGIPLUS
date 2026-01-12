<?php
/**
 * Toutes les requêtes SQL du projet
 */

require_once 'config.php';


// ============================================
// KPIs
// ============================================
$kpis = executeQuery($pdo, "
    SELECT 
        (SELECT COUNT(*) FROM warehouses) as total_warehouses,
        (SELECT COUNT(*) FROM orders WHERE status NOT IN ('cancelled', 'delivered')) as active_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'delivered') as delivered_orders,
        (SELECT COUNT(*) FROM products) as total_products
");
$kpi = $kpis[0] ?? ['total_warehouses' => 0, 'active_orders' => 0, 'delivered_orders' => 0, 'total_products' => 0];

// ============================================
// Q1: Top 10 produits par quantité (30 jours)
// ============================================
$q1 = executeQuery($pdo, "
    SELECT
        p.id,
        p.name,
        SUM(oi.qty_ordered) AS total_qty
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    GROUP BY p.id, p.name
    ORDER BY total_qty DESC
    LIMIT 10
");

// ============================================
// Q4: Valeur des ventes par pays (30 jours)
// ============================================
$q4 = executeQuery($pdo, "
    SELECT
        o.dest_country,
        SUM(oi.qty_ordered * oi.unit_price) AS total_sales
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.dest_country
    ORDER BY total_sales DESC
");

// ============================================
// Q5: Top 10 commandes par valeur
// ============================================
$q5 = executeQuery($pdo, "
    SELECT 
        o.id AS order_id,
        o.order_no,
        o.customer_name,
        SUM(oi.qty_ordered * oi.unit_price) AS total_value
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    GROUP BY o.id, o.order_no, o.customer_name
    ORDER BY total_value DESC
    LIMIT 10
");

// ============================================
// Q8: Top 10 produits par CA (30 jours)
// ============================================
$q8 = executeQuery($pdo, "
    SELECT
        p.id,
        p.name,
        p.sku,
        SUM(oi.qty_ordered) as total_qty,
        SUM(oi.qty_ordered * oi.unit_price) AS revenue
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN products p ON p.id = oi.product_id
    GROUP BY p.id, p.name, p.sku
    ORDER BY revenue DESC
    LIMIT 10
");

// ============================================
// Détails des commandes avec distance spatiale
// ============================================
$orders = executeQuery($pdo, "
    SELECT 
        o.order_no,
        o.customer_name,
        o.order_date,
        o.dest_city,
        o.dest_country,
        o.status,
        ROUND(SUM(oi.qty_ordered * oi.unit_price)::numeric, 2) as order_value,
        SUM(oi.qty_ordered) as total_items,
        (SELECT w.name 
         FROM warehouses w, order_dest_geo odg 
         WHERE odg.order_id = o.id 
         ORDER BY ST_Distance(w.position, odg.geom) 
         LIMIT 1) as nearest_warehouse,
        (SELECT ROUND(MIN(ST_Distance(w.position, odg.geom))::numeric / 1000, 2)
         FROM warehouses w, order_dest_geo odg 
         WHERE odg.order_id = o.id) as distance_km
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id, o.order_no, o.customer_name, o.order_date, o.dest_city, o.dest_country, o.status
    ORDER BY o.order_date DESC
    LIMIT 20
");

// ============================================
// Performance des transporteurs
// ============================================
$carriers = executeQuery($pdo, "
    SELECT 
        carrier as name,
        COUNT(*) as shipment_count,
        ROUND(AVG(eta_date - ship_date)::numeric, 2) as avg_transit_days,
        ROUND(
            COUNT(*) FILTER (WHERE status = 'delivered' AND eta_date >= ship_date) * 100.0 / 
            NULLIF(COUNT(*), 0), 2
        ) as on_time_rate,
        COUNT(*) FILTER (WHERE status = 'delivered') as delivered_count,
        COUNT(*) FILTER (WHERE status = 'in_transit') as in_transit_count,
        COUNT(*) FILTER (WHERE status = 'exception') as exception_count
    FROM shipments
    WHERE status IN ('delivered', 'in_transit', 'exception')
    GROUP BY carrier
    ORDER BY shipment_count DESC
");

// ============================================
// Bandes de distance
// ============================================
$bands = executeQuery($pdo, "
    WITH order_distances AS (
        SELECT 
            ROUND(MIN(ST_Distance(w.position, odg.geom))::numeric / 1000, 2) as distance_km
        FROM orders o
        JOIN order_dest_geo odg ON o.id = odg.order_id
        CROSS JOIN warehouses w
        GROUP BY o.id
    )
    SELECT 
        CASE 
            WHEN distance_km < 50 THEN '< 50 km'
            WHEN distance_km < 150 THEN '50-150 km'
            WHEN distance_km < 300 THEN '150-300 km'
            ELSE '≥ 300 km'
        END as band,
        COUNT(*) as count
    FROM order_distances
    GROUP BY band
    ORDER BY MIN(distance_km)
");

// ============================================
// Coordonnées pour la carte
// ============================================
$warehouses = executeQuery($pdo, "
    SELECT 
        name,
        city,
        country,
        ST_Y(ST_Transform(position, 4326)) as lat,
        ST_X(ST_Transform(position, 4326)) as lon
    FROM warehouses
");

$orders_map = executeQuery($pdo, "
    SELECT 
        o.order_no,
        o.dest_city,
        ST_Y(ST_Transform(odg.geom, 4326)) as lat,
        ST_X(ST_Transform(odg.geom, 4326)) as lon
    FROM orders o
    JOIN order_dest_geo odg ON o.id = odg.order_id
    LIMIT 50
");
?>