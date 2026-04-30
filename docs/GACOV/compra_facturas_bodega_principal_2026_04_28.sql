-- Compras GACOV - carga a bodega principal
-- Fuente: docs/GACOV/surtido_maquinas/*.jpeg
-- Fecha de facturas: 2026-04-24 a 2026-04-28
-- Bodega destino: BODEGA (Bodega Principal)
-- Usuario auditor: admin@gacov.com.co
--
-- Notas:
-- - SQL idempotente: no vuelve a sumar lineas cuyo reference_code ya exista en stock_movements.
-- - unit_cost usa costo neto antes de IVA/impoconsumo cuando la factura separa impuestos.
-- - Las imagenes 11.37.32 y 11.37.50 son planillas de surtido/ruta, no facturas de compra; no se cargan aqui.

START TRANSACTION;

SET @tenant_id := 4;
SET @warehouse_code := 'BODEGA';
SET @performed_by_email := 'admin@gacov.com.co';

SET @warehouse_id := (
    SELECT id
    FROM warehouses
    WHERE tenant_id = @tenant_id
      AND code = @warehouse_code
      AND type = 'bodega'
      AND is_active = 1
    ORDER BY id
    LIMIT 1
);

SET @performed_by := (
    SELECT id
    FROM users
    WHERE tenant_id = @tenant_id
      AND email = @performed_by_email
    ORDER BY id
    LIMIT 1
);

SELECT @tenant_id AS tenant_id, @warehouse_id AS warehouse_id, @performed_by AS performed_by;

DROP TEMPORARY TABLE IF EXISTS tmp_purchase_invoice_lines;
CREATE TEMPORARY TABLE tmp_purchase_invoice_lines (
    line_no INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(20) NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    category VARCHAR(30) NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL DEFAULT 'Und.',
    supplier VARCHAR(150) NOT NULL,
    supplier_sku VARCHAR(60) NULL,
    invoice_ref VARCHAR(50) NOT NULL,
    purchase_date DATE NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    source_note VARCHAR(255) NOT NULL,
    reference_code VARCHAR(50) NOT NULL UNIQUE
) ENGINE=Memory;

INSERT INTO tmp_purchase_invoice_lines
    (product_code, product_name, category, unit_of_measure, supplier, supplier_sku, invoice_ref, purchase_date, quantity, unit_cost, source_note, reference_code)
VALUES
    ('126', 'SODA SCHWEPPES 400ML', 'bebida_fria', 'Und.', 'Coca-Cola FEMSA', '56452', 'COCA-00001', '2026-04-26', 60, 1783.33, 'SCHWEPPES SODA 400ML PET(12), 5 cajas x 12 unidades.', 'COMPRA-COCA-00001-01'),
    ('317', 'BRISA MANZANA 280ML', 'bebida_fria', 'Und.', 'Coca-Cola FEMSA', '92768', 'COCA-00001', '2026-04-26', 480, 1025.00, 'BRISA MANZANA 280ML PET(24), 20 cajas x 24 unidades.', 'COMPRA-COCA-00001-02'),
    ('637', 'BRISA MARACUYA-MANZANA 600ML', 'bebida_fria', 'Und.', 'Coca-Cola FEMSA', '92769/92794', 'COCA-00001', '2026-04-26', 120, 1783.33, 'BRISA MANZANA 600ML 60 und + BRISA MARACUYA 600ML 60 und.', 'COMPRA-COCA-00001-03'),
    ('102', 'COCA-COLA 250ML', 'bebida_fria', 'Und.', 'Coca-Cola FEMSA', '160200', 'COCA-00001', '2026-04-26', 240, 1288.33, 'COCA COLA 250ML, 20 cajas x 12 unidades.', 'COMPRA-COCA-00001-04'),
    ('104', 'COCA-COLA 400ML', 'bebida_fria', 'Und.', 'Coca-Cola FEMSA', '160318', 'COCA-00001', '2026-04-26', 300, 2011.33, 'COCA-COLA 400ML PET, 25 cajas x 12 unidades.', 'COMPRA-COCA-00001-05'),

    ('113', 'LECHE VENDING 3000GR', 'insumo', 'Kg', 'Tecnologia Alimentaria S.A.S. BIC', '29030093', 'FMIT3174', '2026-04-24', 6, 96711.00, 'LECHE PARA VENDING X 3 KILOS, 6 unidades.', 'COMPRA-TALSA-FMIT3174-01'),

    ('687', 'CHOCORAMO BARRITA', 'snack', 'Und.', 'Productos Ramo S.A.S.', '59296', 'IT876672', '2026-04-25', 250, 1175.20, 'CHOCORAMO BARRITA, 50 paquetes x 5 unidades.', 'COMPRA-RAMO-IT876672-01'),
    ('IMP-59945', 'MAIZITOS NATURAL 45G', 'snack', 'Und.', 'Productos Ramo S.A.S.', '59945', 'IT876672', '2026-04-25', 120, 1400.58, 'MAIZITOS NATURAL, 10 paquetes x 12 unidades.', 'COMPRA-RAMO-IT876672-02'),

    ('621', 'GOMAS TROLLY', 'snack', 'Und.', 'Disarco TAT S.A.S.', '2211', '1548841', '2026-04-24', 240, 1011.77, 'CAJA GOMAS TROLLI STDO2 MAGENTA X 24, 10 cajas.', 'COMPRA-DISARCO-1548841-01'),

    ('795', 'PASTEL AREQUIPE', 'snack', 'Und.', 'Productos Alimenticios Las Caseritas S.A.', '0101020', 'TAT50348', '2026-04-27', 36, 1600.72, 'PASTEL HJ AREQ DISPLAY X 12, 3 displays.', 'COMPRA-CASERITAS-TAT50348-01'),
    ('714', 'GALLETA BISCOTTO', 'snack', 'Und.', 'Productos Alimenticios Las Caseritas S.A.', '0104031', 'TAT50348', '2026-04-27', 36, 1588.72, 'BISCOTTO FRUTOS ROJOS DISPLAY X 12, 3 displays.', 'COMPRA-CASERITAS-TAT50348-02'),

    ('115', 'CAPPUCCINO VAINILLA 1000GR', 'insumo', 'Kg', 'SH&M Insumos S.A.S.', '01', 'PE8965', '2026-04-25', 20, 42016.80, 'CAPUCCINO VAINILLA DILATTE VENDING x Kg.', 'COMPRA-SHM-PE8965-01'),
    ('117', 'CAPPUCCINO AMARETTO', 'insumo', 'Kg', 'SH&M Insumos S.A.S.', '02', 'PE8965', '2026-04-25', 6, 42017.17, 'CAPPUCCINO AMARETTO DILATTE VENDING x Kg.', 'COMPRA-SHM-PE8965-02'),
    ('118', 'AROMATICA - TE JENGIBRE', 'insumo', 'Kg', 'SH&M Insumos S.A.S.', '19', 'PE8965', '2026-04-25', 6, 22353.00, 'TE QFC SABOR JENGIBRE LIMON x Kg.', 'COMPRA-SHM-PE8965-03'),

    ('718', 'SOTONICO INN TROPICAL 600ML', 'bebida_fria', 'Und.', 'RTD S.A.S.', '905066', 'PLANILLA19650', '2026-04-25', 48, 1193.56, 'ISOTONICO INN TROPICAL 600ML, 4 pacas.', 'COMPRA-RTD-19650-01'),
    ('66', 'AGUA 600ML', 'bebida_fria', 'Und.', 'RTD S.A.S.', '905075', 'PLANILLA19650', '2026-04-25', 480, 538.00, 'AGUA INN SIN GAS 600ML TIPO BALA, 40 pacas.', 'COMPRA-RTD-19650-02'),
    ('720', 'AGUA SABORIZADA INN', 'bebida_fria', 'Und.', 'RTD S.A.S.', '905119/905120/905223/905278', 'PLANILLA19650', '2026-04-25', 240, 1132.88, 'AGUA SABORIZADA INN surtida: limon, maracuya, frutos rojos y manzana.', 'COMPRA-RTD-19650-03'),
    ('IMP-905528', 'ISOTONICO INN MARACUYA 600ML', 'bebida_fria', 'Und.', 'RTD S.A.S.', '905528', 'PLANILLA19650', '2026-04-25', 48, 1193.56, 'ISOTONICO INN MARACUYA 600ML, 4 pacas.', 'COMPRA-RTD-19650-04'),

    ('68', 'SPORADE 500ML', 'bebida_fria', 'Und.', 'AJE Colombia S.A.S.', 'PTE10034', 'CMEA368534', '2026-04-27', 36, 1750.69, 'SPORADE FRUT.TROP.REG PET 0.5LT, 3 pacas.', 'COMPRA-AJE-CMEA368534-01'),
    ('IMP-PTE10126', 'CIFRUT FRUIT REG PET 400ML', 'bebida_fria', 'Und.', 'AJE Colombia S.A.S.', 'PTE10126', 'CMEA368534', '2026-04-27', 144, 910.37, 'CIFRUT FRUIT REG PET 0.4LT, 12 pacas.', 'COMPRA-AJE-CMEA368534-02'),
    ('IMP-PTE10163', 'CIFRUT MORA REG PET 400ML', 'bebida_fria', 'Und.', 'AJE Colombia S.A.S.', 'PTE10163', 'CMEA368534', '2026-04-27', 144, 910.36, 'CIFRUT MORA REG PET 0.4LT, 12 pacas.', 'COMPRA-AJE-CMEA368534-03'),

    ('86', 'MR TEA 500ML', 'bebida_fria', 'Und.', 'Postobon S.A.', '23601', 'IT081369920', '2026-04-28', 120, 1907.43, 'LIMON MR TEA 500ML PET X 12.', 'COMPRA-POSTOBON-IT081369920-01'),
    ('87', 'HIT CAJA 200ML', 'bebida_fria', 'Und.', 'Postobon S.A.', '23230/23236/23235/23237', 'IT081369920', '2026-04-28', 144, 980.39, 'HIT 200ML surtido: lulo 24, naranja pina 24, mango 48, mora 48.', 'COMPRA-POSTOBON-IT081369920-02'),
    ('82', 'HIT 500ML', 'bebida_fria', 'Und.', 'Postobon S.A.', '23243/23278/23242/23245', 'IT081369920', '2026-04-28', 120, 2100.84, 'HIT 500ML surtido: naranja pina 24, lulo 24, frutas tropicales 36, mora 36.', 'COMPRA-POSTOBON-IT081369920-03'),
    ('65', 'AGUA PEQUEÑA 300ML', 'bebida_fria', 'Und.', 'Postobon S.A.', '31844', 'IT081369960', '2026-04-28', 480, 583.33, 'AGUA CRISTAL PLANA PET 300ML X24.', 'COMPRA-POSTOBON-IT081369960-01'),

    ('694', 'MILO GALLETAS ANILLOS', 'snack', 'Und.', 'D&F Distribuciones Sur S.A.S.', '12382987', 'DF701356', '2026-04-27', 128, 1046.94, 'MILO GALLETA ANILLOS, 8 displays x 16 unidades.', 'COMPRA-DF-DF701356-01'),
    ('124', 'COCOSETTE WAFER', 'snack', 'Und.', 'D&F Distribuciones Sur S.A.S.', '12610360', 'DF701356', '2026-04-27', 36, 1465.94, 'COCOSETTE WAFER, 2 displays x 18 unidades.', 'COMPRA-DF-DF701356-02'),
    ('IMP-12962615', 'COCOSETTE WAFER LIMONADA', 'snack', 'Und.', 'D&F Distribuciones Sur S.A.S.', '12962615', 'DF701356', '2026-04-27', 54, 1465.94, 'COCOSETTE WAFER LIMONADA, 3 displays x 18 unidades.', 'COMPRA-DF-DF701356-03'),

    ('114', 'CHOCOLATE CORONA 1000GR', 'insumo', 'Kg', 'Novaventa S.A.S.', '1009932', 'NO2-1145671', '2026-04-27', 6, 37193.00, 'CHOCOL CORONA VENDING 12BOLX1KG.', 'COMPRA-NOVAVENTA-NO21145671-01'),
    ('14', 'GTA MINICHIPS', 'snack', 'Und.', 'Novaventa S.A.S.', '1049449', 'NO2-1145671', '2026-04-27', 60, 1075.67, 'GTA FESTIVAL MINICHIPS CHOC BS X12, 5 displays.', 'COMPRA-NOVAVENTA-NO21145671-02'),
    ('716', 'GALLETA FESTIVAL', 'snack', 'Und.', 'Novaventa S.A.S.', '1001572/1001573/1001574', 'NO2-1145671', '2026-04-27', 108, 739.50, 'GTA FESTIVAL surtida: limon 36, fresa 36, chocolate 36.', 'COMPRA-NOVAVENTA-NO21145671-03'),
    ('26', 'GALLETA DUX QUESO', 'snack', 'Und.', 'Novaventa S.A.S.', '1029564', 'NO2-1145671', '2026-04-27', 144, 268.92, 'DUX RELLENA DE QUESO 6X24.', 'COMPRA-NOVAVENTA-NO21145671-04'),
    ('16', 'GALLETA TOSH', 'snack', 'Und.', 'Novaventa S.A.S.', '1078185', 'NO2-1145671', '2026-04-27', 216, 729.78, 'GTA TOSH MIEL FIT BS 9X3, 24 displays x 9 unidades.', 'COMPRA-NOVAVENTA-NO21145671-05'),
    ('40', 'CHOCOLATINA GOL', 'snack', 'Und.', 'Novaventa S.A.S.', '1081626', 'NO2-1145671', '2026-04-27', 48, 1008.50, 'GOL 12PLGX24UNX31G, 2 displays x 24 unidades.', 'COMPRA-NOVAVENTA-NO21145671-06'),
    ('48', 'MANI KRAKS', 'snack', 'Und.', 'Novaventa S.A.S.', '1042889', 'NO2-1145671', '2026-04-27', 96, 1008.08, 'PASABOCAS LA ESPECIAL KRAKS, 8 displays x 12 unidades.', 'COMPRA-NOVAVENTA-NO21145671-07'),
    ('705', 'TOCINETAS', 'snack', 'Und.', 'Novaventa S.A.S.', '1080579', 'NO2-1145671', '2026-04-27', 60, 1143.00, 'TOCINETAS FRED CON MIEL 25GX48, 48 unidades + 12 sueltas.', 'COMPRA-NOVAVENTA-NO21145671-08');

DROP TEMPORARY TABLE IF EXISTS tmp_purchase_pending;
CREATE TEMPORARY TABLE tmp_purchase_pending AS
SELECT l.*
FROM tmp_purchase_invoice_lines l
WHERE NOT EXISTS (
    SELECT 1
    FROM stock_movements sm
    WHERE sm.tenant_id = @tenant_id
      AND sm.reference_code = l.reference_code
);

INSERT INTO products (
    tenant_id,
    code,
    worldoffice_code,
    name,
    category,
    unit_of_measure,
    cost,
    min_sale_price,
    unit_price,
    min_stock_alert,
    supplier,
    supplier_sku,
    purchase_date,
    is_active,
    created_at,
    updated_at
)
SELECT
    @tenant_id,
    grouped.product_code,
    NULL,
    grouped.product_name,
    grouped.category,
    grouped.unit_of_measure,
    grouped.cost,
    0,
    0,
    0,
    grouped.supplier,
    grouped.supplier_sku,
    grouped.purchase_date,
    1,
    NOW(),
    NOW()
FROM (
    SELECT
        product_code,
        SUBSTRING_INDEX(GROUP_CONCAT(product_name ORDER BY line_no SEPARATOR '||'), '||', 1) AS product_name,
        SUBSTRING_INDEX(GROUP_CONCAT(category ORDER BY line_no SEPARATOR '||'), '||', 1) AS category,
        SUBSTRING_INDEX(GROUP_CONCAT(unit_of_measure ORDER BY line_no SEPARATOR '||'), '||', 1) AS unit_of_measure,
        ROUND(SUM(quantity * unit_cost) / NULLIF(SUM(quantity), 0), 2) AS cost,
        SUBSTRING_INDEX(GROUP_CONCAT(supplier ORDER BY purchase_date DESC, line_no DESC SEPARATOR '||'), '||', 1) AS supplier,
        SUBSTRING_INDEX(GROUP_CONCAT(supplier_sku ORDER BY purchase_date DESC, line_no DESC SEPARATOR '||'), '||', 1) AS supplier_sku,
        MAX(purchase_date) AS purchase_date
    FROM tmp_purchase_pending
    GROUP BY product_code
) AS grouped
ON DUPLICATE KEY UPDATE
    cost = VALUES(cost),
    supplier = VALUES(supplier),
    supplier_sku = COALESCE(NULLIF(products.supplier_sku, ''), VALUES(supplier_sku)),
    purchase_date = VALUES(purchase_date),
    is_active = 1,
    updated_at = NOW();

INSERT INTO stock (
    tenant_id,
    warehouse_id,
    product_id,
    quantity,
    min_quantity,
    updated_at
)
SELECT
    @tenant_id,
    @warehouse_id,
    p.id,
    0,
    0,
    NOW()
FROM tmp_purchase_pending l
JOIN products p ON p.code = l.product_code
GROUP BY p.id
ON DUPLICATE KEY UPDATE
    tenant_id = COALESCE(stock.tenant_id, VALUES(tenant_id)),
    updated_at = NOW();

UPDATE stock s
JOIN (
    SELECT
        p.id AS product_id,
        SUM(l.quantity) AS quantity_to_add
    FROM tmp_purchase_pending l
    JOIN products p ON p.code = l.product_code
    GROUP BY p.id
) AS pending ON pending.product_id = s.product_id
SET
    s.quantity = s.quantity + pending.quantity_to_add,
    s.tenant_id = COALESCE(s.tenant_id, @tenant_id),
    s.updated_at = NOW()
WHERE s.warehouse_id = @warehouse_id;

INSERT INTO stock_movements (
    tenant_id,
    movement_type,
    origin_warehouse_id,
    destination_warehouse_id,
    product_id,
    quantity,
    unit_cost,
    reference_code,
    notes,
    performed_by,
    created_at
)
SELECT
    @tenant_id,
    'carga_inicial',
    NULL,
    @warehouse_id,
    p.id,
    l.quantity,
    l.unit_cost,
    l.reference_code,
    CONCAT('Compra ', l.supplier, ' factura ', l.invoice_ref, ' (', DATE_FORMAT(l.purchase_date, '%Y-%m-%d'), '). ', l.source_note),
    @performed_by,
    NOW()
FROM tmp_purchase_pending l
JOIN products p ON p.code = l.product_code;

SELECT
    COUNT(*) AS lineas_nuevas,
    COALESCE(SUM(quantity), 0) AS unidades_sumadas
FROM tmp_purchase_pending;

COMMIT;
