<?php
// includes/db.php - MMK Hub Database Layer
// Supports SQLite (default, zero-setup) and MySQL/MariaDB (XAMPP localhost)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbFile = __DIR__ . '/../data/mmkhub.db';
        $dataDir = dirname($dbFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        try {
            self::$pdo = new PDO("sqlite:" . $dbFile);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::initializeTables(self::$pdo);
            return self::$pdo;
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    private static function initializeTables(PDO $pdo): void {
        // Products & Services
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku TEXT UNIQUE,
            name_en TEXT NOT NULL,
            name_fr TEXT NOT NULL,
            category TEXT NOT NULL,
            description_en TEXT,
            description_fr TEXT,
            image TEXT,
            is_service INTEGER DEFAULT 0,
            is_bundle INTEGER DEFAULT 0,
            retail_price REAL NOT NULL,
            wholesale_price REAL NOT NULL,
            moq INTEGER DEFAULT 1,
            stock INTEGER DEFAULT 100,
            tier_discounts TEXT,
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Users & Wholesale registrations
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL UNIQUE,
            email TEXT,
            password TEXT,
            role TEXT DEFAULT 'retail',
            company_name TEXT,
            tax_id TEXT,
            city TEXT DEFAULT 'Douala',
            address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Orders
        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_code TEXT UNIQUE,
            user_id INTEGER,
            customer_name TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            customer_email TEXT,
            city TEXT NOT NULL,
            delivery_address TEXT NOT NULL,
            delivery_method TEXT DEFAULT 'standard',
            delivery_fee REAL DEFAULT 0,
            order_type TEXT DEFAULT 'retail',
            subtotal REAL NOT NULL,
            discount_amount REAL DEFAULT 0,
            total_amount REAL NOT NULL,
            deposit_amount REAL DEFAULT 0,
            balance_amount REAL DEFAULT 0,
            payment_method TEXT NOT NULL,
            payment_status TEXT DEFAULT 'pending',
            fulfillment_status TEXT DEFAULT 'received',
            payment_ref TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Order Items
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            unit_price REAL NOT NULL,
            quantity INTEGER NOT NULL,
            subtotal REAL NOT NULL,
            is_service INTEGER DEFAULT 0,
            service_date TEXT,
            service_slot TEXT,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )");

        // Quotes
        $pdo->exec("CREATE TABLE IF NOT EXISTS quotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quote_code TEXT UNIQUE,
            customer_name TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            customer_email TEXT,
            company_name TEXT,
            product_id INTEGER,
            product_name TEXT NOT NULL,
            requested_qty INTEGER NOT NULL,
            target_price REAL,
            quoted_price REAL,
            status TEXT DEFAULT 'new',
            message TEXT,
            owner_reply TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Service Bookings
        $pdo->exec("CREATE TABLE IF NOT EXISTS service_bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER,
            service_id INTEGER NOT NULL,
            customer_name TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            service_date TEXT NOT NULL,
            service_slot TEXT NOT NULL,
            tier TEXT DEFAULT 'Standard',
            address TEXT NOT NULL,
            status TEXT DEFAULT 'confirmed',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Settings table
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            key_name TEXT PRIMARY KEY,
            key_value TEXT
        )");

        self::seedData($pdo);
    }

    private static function seedData(PDO $pdo): void {
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        if ($count > 0) {
            return;
        }

        // Insert Admin user
        $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, company_name, city) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['MMK Hub Owner', '+237670000000', 'owner@mmkhub.cm', password_hash('admin123', PASSWORD_DEFAULT), 'admin', 'MMK Hub SARL', 'Douala']);

        // Insert Sample Wholesale Approved Buyer
        $stmt->execute(['Kenfack Trading & Tech', '+237699112233', 'kenfack@trading.cm', password_hash('buyer123', PASSWORD_DEFAULT), 'wholesale_approved', 'Kenfack Electronics SARL', 'Yaoundé']);
        
        // Insert Sample Wholesale Pending Buyer
        $stmt->execute(['Bafoussam Hardware Hub', '+237677889900', 'bafoussam@hardware.cm', password_hash('buyer123', PASSWORD_DEFAULT), 'wholesale_pending', 'Bafoussam Outillage Ets', 'Bafoussam']);

        // Insert Sample Products
        $products = [
            [
                'sku' => 'SLR-LNT-01',
                'name_en' => 'Commercial Solar Home Lighting & Multi-Device Charging Kit',
                'name_fr' => 'Kit d\'Éclairage Solaire Domestique & Chargeur Multi-Appareils',
                'category' => 'Solar Energy',
                'description_en' => 'High-capacity solar home system with 30W solar panel, 4 ultra-bright LED lamps, portable battery bank, and fast USB multi-device charging hub. Ideal for residences, rural shops, and off-grid reliability in Cameroon.',
                'description_fr' => 'Système solaire haute capacité avec panneau 30W, 4 lampes LED ultra-lumineuses, batterie lithium et concentrateur de charge USB rapide. Idéal pour résidences, commerces et zones hors-réseau.',
                'image' => 'assets/images/solar_lantern.jpg',
                'is_service' => 0,
                'is_bundle' => 0,
                'retail_price' => 28500,
                'wholesale_price' => 22000,
                'moq' => 10,
                'stock' => 350,
                'tier_discounts' => json_encode([
                    ['min' => 10, 'discount_pct' => 15, 'price' => 22000],
                    ['min' => 50, 'discount_pct' => 20, 'price' => 20500],
                    ['min' => 100, 'discount_pct' => 25, 'price' => 19000]
                ])
            ],
            [
                'sku' => 'TEX-WAX-02',
                'name_en' => 'Premium African Wax Print Cotton Fabric (Hollandais 6 Yards)',
                'name_fr' => 'Tissu Pagne Wax Africain Véritable Coton (Hollandais 6 Yards)',
                'category' => 'African Textiles',
                'description_en' => '100% authentic vibrant African wax print cotton textile. Durable colors, premium woven finish. Highly demanded by tailors, fashion designers, and retail textile boutiques.',
                'description_fr' => 'Tissu wax africain 100% coton véritable. Couleurs éclatantes, finitions supérieures. Très prisé par les couturiers, stylistes et boutiques de prêt-à-porter.',
                'image' => 'assets/images/wax_print.jpg',
                'is_service' => 0,
                'is_bundle' => 0,
                'retail_price' => 18000,
                'wholesale_price' => 13500,
                'moq' => 20,
                'stock' => 500,
                'tier_discounts' => json_encode([
                    ['min' => 20, 'discount_pct' => 15, 'price' => 13500],
                    ['min' => 50, 'discount_pct' => 22, 'price' => 12500],
                    ['min' => 100, 'discount_pct' => 28, 'price' => 11500]
                ])
            ],
            [
                'sku' => 'BLD-CEM-03',
                'name_en' => 'CIMENCAM / Dangote 42.5R High-Grade Cement (50kg Bag)',
                'name_fr' => 'Ciment Haute Résistance 42.5R CIMENCAM / Dangote (Sac 50kg)',
                'category' => 'Building Materials',
                'description_en' => 'Certified high-performance Portland cement class 42.5R for reinforced concrete, heavy foundations, commercial structures, and construction sites across Douala, Yaoundé, and West Region.',
                'description_fr' => 'Ciment Portland certifié haute résistance classe 42.5R pour béton armé, fondations et chantiers de construction à Douala, Yaoundé et tout le Cameroun.',
                'image' => 'assets/images/building_materials.jpg',
                'is_service' => 0,
                'is_bundle' => 0,
                'retail_price' => 5400,
                'wholesale_price' => 4600,
                'moq' => 50,
                'stock' => 2400,
                'tier_discounts' => json_encode([
                    ['min' => 50, 'discount_pct' => 12, 'price' => 4600],
                    ['min' => 200, 'discount_pct' => 15, 'price' => 4500],
                    ['min' => 500, 'discount_pct' => 18, 'price' => 4350]
                ])
            ],
            [
                'sku' => 'SRV-SLR-04',
                'name_en' => 'Solar System & Inverter Certified On-Site Installation',
                'name_fr' => 'Service d\'Installation Professionnelle Panneaux Solaires & Onduleurs',
                'category' => 'Services',
                'description_en' => 'Comprehensive technical installation, cabling, inverter configuration, and testing by certified solar engineers. Available in Douala, Yaoundé, Buea, Limbe, and Bafoussam.',
                'description_fr' => 'Installation technique complète, câblage, raccordement onduleur et tests par des ingénieurs solaires certifiés. Disponible à Douala, Yaoundé, Buea, Limbe et Bafoussam.',
                'image' => 'assets/images/solar_installation.jpg',
                'is_service' => 1,
                'is_bundle' => 0,
                'retail_price' => 35000,
                'wholesale_price' => 28000,
                'moq' => 1,
                'stock' => 99,
                'tier_discounts' => json_encode([
                    ['tier' => 'Basic (1-3 Panels)', 'price' => 35000],
                    ['tier' => 'Standard Commercial (4-10 Panels)', 'price' => 75000],
                    ['tier' => 'Industrial / Farm (10+ Panels)', 'price' => 150000]
                ])
            ],
            [
                'sku' => 'BDL-SLR-05',
                'name_en' => 'Complete Solar Bundle: 200W Commercial System + Full Installation',
                'name_fr' => 'Pack Clé en Main Solaire : Kit 200W + Installation Complète sur Site',
                'category' => 'Bundles',
                'description_en' => 'Turnkey solar energy solution: includes 200W mono solar panels, hybrid pure sine wave inverter, 100Ah deep-cycle gel battery, protective brackets, and on-site expert installation.',
                'description_fr' => 'Solution solaire clé en main : 2 panneaux solaires 100W, onduleur hybride pur sinus, batterie gel 100Ah, coffret de protection et installation complète incluse.',
                'image' => 'assets/images/solar_lantern.jpg',
                'is_service' => 0,
                'is_bundle' => 1,
                'retail_price' => 185000,
                'wholesale_price' => 155000,
                'moq' => 3,
                'stock' => 45,
                'tier_discounts' => json_encode([
                    ['min' => 3, 'discount_pct' => 12, 'price' => 155000],
                    ['min' => 10, 'discount_pct' => 18, 'price' => 145000]
                ])
            ]
        ];

        $insertProd = $pdo->prepare("INSERT INTO products (sku, name_en, name_fr, category, description_en, description_fr, image, is_service, is_bundle, retail_price, wholesale_price, moq, stock, tier_discounts) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $p) {
            $insertProd->execute([
                $p['sku'], $p['name_en'], $p['name_fr'], $p['category'], $p['description_en'], $p['description_fr'], $p['image'],
                $p['is_service'], $p['is_bundle'], $p['retail_price'], $p['wholesale_price'], $p['moq'], $p['stock'], $p['tier_discounts']
            ]);
        }

        // Insert Default Settings
        $settings = [
            'site_name' => 'MMK Hub Cameroon',
            'momo_phone' => '+237 671 234 567',
            'orange_phone' => '+237 691 234 567',
            'whatsapp_phone' => '237670000000',
            'support_email' => 'contact@mmkhub.cm',
            'warehouse_city' => 'Douala (Akwa / Bonabéri)',
            'wholesale_deposit_pct' => '50'
        ];
        $stmtSet = $pdo->prepare("INSERT OR REPLACE INTO settings (key_name, key_value) VALUES (?, ?)");
        foreach ($settings as $k => $v) {
            $stmtSet->execute([$k, $v]);
        }

        // Sample initial orders
        $orderCode1 = 'MMK-78419';
        $pdo->exec("INSERT INTO orders (order_code, customer_name, customer_phone, customer_email, city, delivery_address, delivery_method, delivery_fee, order_type, subtotal, discount_amount, total_amount, deposit_amount, balance_amount, payment_method, payment_status, fulfillment_status, payment_ref) VALUES (
            '{$orderCode1}', 'Tchinda Joseph', '+237677443322', 'tchinda@gmail.com', 'Douala', 'Bonapriso, Rue des Palmiers', 'express', 1500, 'retail', 28500, 0, 30000, 30000, 0, 'momo', 'fully_paid', 'shipped', 'MOMO-789012'
        )");
        $orderId1 = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (
            {$orderId1}, 1, 'Commercial Solar Home Lighting & Charging Kit', 28500, 1, 28500
        )");

        $orderCode2 = 'MMK-99214';
        $pdo->exec("INSERT INTO orders (order_code, customer_name, customer_phone, customer_email, city, delivery_address, delivery_method, delivery_fee, order_type, subtotal, discount_amount, total_amount, deposit_amount, balance_amount, payment_method, payment_status, fulfillment_status, payment_ref) VALUES (
            '{$orderCode2}', 'Kenfack Electronics SARL', '+237699112233', 'kenfack@trading.cm', 'Yaoundé', 'Marché Mokolo, Bloc B', 'freight', 0, 'wholesale', 440000, 0, 440000, 220000, 220000, 'deposit_orange', 'deposit_paid', 'processing', 'OM-883921'
        )");
        $orderId2 = $pdo->lastInsertId();
        $pdo->exec("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal) VALUES (
            {$orderId2}, 1, 'Commercial Solar Home Lighting & Charging Kit (Wholesale Lot)', 22000, 20, 440000
        )");
    }
}

// Global Helpers
function get_db(): PDO {
    return Database::getConnection();
}

function format_cfa(float|int $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function get_lang(): string {
    if (isset($_GET['lang'])) {
        $_SESSION['lang'] = in_array($_GET['lang'], ['en', 'fr']) ? $_GET['lang'] : 'en';
    }
    return $_SESSION['lang'] ?? 'en';
}

function t(string $en, string $fr): string {
    return get_lang() === 'fr' ? $fr : $en;
}

function get_user_role(): string {
    if (isset($_SESSION['demo_role'])) {
        return $_SESSION['demo_role'];
    }
    if (isset($_SESSION['user']['role'])) {
        return $_SESSION['user']['role'];
    }
    return 'retail';
}

function is_wholesale(): bool {
    // wholesale_suspended buyers are blocked
    if (isset($_SESSION['channel_mode']) && $_SESSION['channel_mode'] === 'wholesale') {
        return true;
    }
    return get_user_role() === 'wholesale_approved';
}

function is_admin(): bool {
    return get_user_role() === 'admin';
}
