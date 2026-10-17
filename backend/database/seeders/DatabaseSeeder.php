<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with rich, realistic demo data.
     */
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. USERS & ROLES
        // -------------------------------------------------------------
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
                'notify_low_stock' => true,
                'notify_purchase_orders' => true,
            ],
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Sarah Connor',
                'password' => 'password',
                'role' => 'manager',
                'notify_low_stock' => true,
                'notify_purchase_orders' => true,
            ],
        );

        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Alex Murphy',
                'password' => 'password',
                'role' => 'staff',
                'notify_low_stock' => false,
                'notify_purchase_orders' => false,
            ],
        );

        // -------------------------------------------------------------
        // 2. CATEGORIES
        // -------------------------------------------------------------
        $catElectronics = Category::firstOrCreate(
            ['name' => 'Electronics & Hardware'],
            ['description' => 'Scanners, thermal printers, docking stations, and peripherals.'],
        );

        $catPackaging = Category::firstOrCreate(
            ['name' => 'Packaging & Shipping'],
            ['description' => 'Corrugated boxes, thermal labels, stretch wrap, and tapes.'],
        );

        $catWarehouse = Category::firstOrCreate(
            ['name' => 'Warehouse & Facilities'],
            ['description' => 'Utility carts, scales, anti-fatigue mats, and storage equipment.'],
        );

        $catSafety = Category::firstOrCreate(
            ['name' => 'Safety & PPE'],
            ['description' => 'High-visibility apparel, protective gloves, goggles, and gear.'],
        );

        // -------------------------------------------------------------
        // 3. WAREHOUSES
        // -------------------------------------------------------------
        $whMain = Warehouse::firstOrCreate(
            ['name' => 'Main Distribution Center'],
            ['address' => '100 Logistics Blvd, Chicago, IL 60601', 'is_active' => true],
        );

        $whWest = Warehouse::firstOrCreate(
            ['name' => 'West Coast Hub'],
            ['address' => '450 Harbor Way, Long Beach, CA 90802', 'is_active' => true],
        );

        $whEast = Warehouse::firstOrCreate(
            ['name' => 'East Coast Depot'],
            ['address' => '220 Atlantic Ave, Newark, NJ 07102', 'is_active' => true],
        );

        // -------------------------------------------------------------
        // 4. SUPPLIERS
        // -------------------------------------------------------------
        $supTech = Supplier::firstOrCreate(
            ['email' => 'sales@techcorp.io'],
            [
                'name' => 'TechCorp Global Logistics',
                'phone' => '+1 (555) 234-5678',
                'address' => '101 Silicon Valley Way, San Jose, CA 95110',
                'notes' => 'Primary vendor for barcode scanners, label printers, and docks. Standard payment net-30.',
            ],
        );

        $supApex = Supplier::firstOrCreate(
            ['email' => 'orders@apexpack.com'],
            [
                'name' => 'Apex Packaging Supplies',
                'phone' => '+1 (555) 876-5432',
                'address' => '88 Industrial Pkwy, Cleveland, OH 44101',
                'notes' => 'Bulk packaging supplier. Free freight on orders above $1,500.',
            ],
        );

        $supShield = Supplier::firstOrCreate(
            ['email' => 'orders@shieldsafe.com'],
            [
                'name' => 'ShieldSafe Protective Gear',
                'phone' => '+1 (555) 345-6789',
                'address' => '312 Protection Way, Houston, TX 77001',
                'notes' => 'OSHA-certified PPE, safety vests, gloves, and protective eyewear.',
            ],
        );

        $supPrecision = Supplier::firstOrCreate(
            ['email' => 'sales@precisiontools.com'],
            [
                'name' => 'Precision Tools & Carts Co',
                'phone' => '+1 (555) 987-6543',
                'address' => '500 Grand Ave, Grand Rapids, MI 49503',
                'notes' => 'Industrial scales, steel utility carts, and facility floor fixtures.',
            ],
        );

        // -------------------------------------------------------------
        // 5. CUSTOMERS
        // -------------------------------------------------------------
        $custAcme = Customer::firstOrCreate(
            ['email' => 'purchasing@acmeretail.com'],
            [
                'name' => 'Jane Cooper',
                'company_name' => 'Acme Retail Corporation',
                'phone' => '+1 (555) 111-2222',
                'address' => '742 Evergreen Terrace, Springfield, OR 97477',
                'tax_number' => 'US-849201928',
                'is_active' => true,
            ],
        );

        $custStark = Customer::firstOrCreate(
            ['email' => 'procurement@starkind.com'],
            [
                'name' => 'Tony Stark',
                'company_name' => 'Stark Logistics & Aerospace',
                'phone' => '+1 (555) 333-4444',
                'address' => '10880 Malibu Point, Malibu, CA 90265',
                'tax_number' => 'US-992817263',
                'is_active' => true,
            ],
        );

        $custWayne = Customer::firstOrCreate(
            ['email' => 'supply@wayneent.com'],
            [
                'name' => 'Bruce Wayne',
                'company_name' => 'Wayne Commercial Enterprises',
                'phone' => '+1 (555) 555-6666',
                'address' => '1007 Mountain Drive, Gotham, NJ 07001',
                'tax_number' => 'US-771625344',
                'is_active' => true,
            ],
        );

        $custCyberdyne = Customer::firstOrCreate(
            ['email' => 'orders@cyberdyne.net'],
            [
                'name' => 'Miles Dyson',
                'company_name' => 'Cyberdyne Systems Corp',
                'phone' => '+1 (555) 777-8888',
                'address' => '18144 El Camino Real, Sunnyvale, CA 94086',
                'tax_number' => 'US-443322110',
                'is_active' => true,
            ],
        );

        $custInitech = Customer::firstOrCreate(
            ['email' => 'operations@initech.com'],
            [
                'name' => 'Peter Gibbons',
                'company_name' => 'Initech Software Solutions',
                'phone' => '+1 (555) 888-9999',
                'address' => '4120 Freemont Ave, Austin, TX 78701',
                'tax_number' => 'US-123456789',
                'is_active' => true,
            ],
        );

        // -------------------------------------------------------------
        // 6. PRODUCTS CATALOG
        // -------------------------------------------------------------
        $productsData = [
            [
                'sku' => 'PRD-00001',
                'barcode' => '8901234567890',
                'name' => 'Pro Wireless 2D Barcode Scanner',
                'description' => 'Long-range 2.4G & Bluetooth handheld barcode reader with charging cradle.',
                'category_id' => $catElectronics->id,
                'unit' => 'pcs',
                'cost_price' => 45.00,
                'sell_price' => 89.99,
                'reorder_level' => 15,
                'suppliers' => [
                    $supTech->id => ['supplier_sku' => 'TC-SCAN-2D', 'unit_cost' => 45.00, 'lead_time_days' => 5, 'min_order_quantity' => 10, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 45,
                    $whWest->id => 20,
                    $whEast->id => 12,
                ],
            ],
            [
                'sku' => 'PRD-00002',
                'barcode' => '8901234567891',
                'name' => 'Direct Thermal Shipping Labels 4x6 (Pack of 4)',
                'description' => 'Commercial grade fanfold & roll labels. Perforated for easy peeling, smudge-free.',
                'category_id' => $catPackaging->id,
                'unit' => 'box',
                'cost_price' => 12.50,
                'sell_price' => 24.99,
                'reorder_level' => 30,
                'suppliers' => [
                    $supApex->id => ['supplier_sku' => 'APX-LBL-4X6', 'unit_cost' => 12.50, 'lead_time_days' => 3, 'min_order_quantity' => 25, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 120,
                    $whWest->id => 45,
                    $whEast->id => 35,
                ],
            ],
            [
                'sku' => 'PRD-00003',
                'barcode' => '8901234567892',
                'name' => 'Heavy-Duty Corrugated Shipping Boxes 18x14x12',
                'description' => 'Double-wall ECT-44 corrugated shipping cartons. Bundle of 25 boxes.',
                'category_id' => $catPackaging->id,
                'unit' => 'bundle',
                'cost_price' => 22.00,
                'sell_price' => 44.50,
                'reorder_level' => 20,
                'suppliers' => [
                    $supApex->id => ['supplier_sku' => 'APX-BOX-1814', 'unit_cost' => 22.00, 'lead_time_days' => 4, 'min_order_quantity' => 20, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 75,
                    $whWest->id => 30,
                    $whEast->id => 25,
                ],
            ],
            [
                'sku' => 'PRD-00004',
                'barcode' => '8901234567893',
                'name' => 'USB-C Triple Display 10-in-1 Docking Station',
                'description' => 'Dual HDMI 4K + DisplayPort, 100W Power Delivery, Gigabit Ethernet, 4x USB-A.',
                'category_id' => $catElectronics->id,
                'unit' => 'pcs',
                'cost_price' => 65.00,
                'sell_price' => 129.99,
                'reorder_level' => 10,
                // Intentionally low stock to trigger low-stock alerts
                'suppliers' => [
                    $supTech->id => ['supplier_sku' => 'TC-DOCK-10IN1', 'unit_cost' => 65.00, 'lead_time_days' => 7, 'min_order_quantity' => 5, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 4, // Under reorder level!
                    $whWest->id => 2,
                    $whEast->id => 1,
                ],
            ],
            [
                'sku' => 'PRD-00005',
                'barcode' => '8901234567894',
                'name' => 'Ergonomic Anti-Fatigue Warehouse Floor Mat (3x5 ft)',
                'description' => 'High-density closed-cell foam with beveled safety edges. Stain & grease resistant.',
                'category_id' => $catWarehouse->id,
                'unit' => 'pcs',
                'cost_price' => 32.00,
                'sell_price' => 64.95,
                'reorder_level' => 8,
                'suppliers' => [
                    $supPrecision->id => ['supplier_sku' => 'PRC-MAT-3X5', 'unit_cost' => 32.00, 'lead_time_days' => 6, 'min_order_quantity' => 4, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 18,
                    $whWest->id => 8,
                    $whEast->id => 6,
                ],
            ],
            [
                'sku' => 'PRD-00006',
                'barcode' => '8901234567895',
                'name' => 'High-Visibility Class 2 Zipper Safety Vest (Large)',
                'description' => 'ANSI/ISEA compliant breathable polyester mesh with 2-inch reflective stripes.',
                'category_id' => $catSafety->id,
                'unit' => 'pcs',
                'cost_price' => 6.50,
                'sell_price' => 15.99,
                'reorder_level' => 25,
                'suppliers' => [
                    $supShield->id => ['supplier_sku' => 'SS-VEST-L', 'unit_cost' => 6.50, 'lead_time_days' => 3, 'min_order_quantity' => 25, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 60,
                    $whWest->id => 30,
                    $whEast->id => 20,
                ],
            ],
            [
                'sku' => 'PRD-00007',
                'barcode' => '8901234567896',
                'name' => 'Cut-Resistant Nitrile Coated Work Gloves (Pair)',
                'description' => 'ANSI Level A4 cut resistance with micro-foam nitrile palm for secure grip.',
                'category_id' => $catSafety->id,
                'unit' => 'pair',
                'cost_price' => 3.20,
                'sell_price' => 8.50,
                'reorder_level' => 40,
                'suppliers' => [
                    $supShield->id => ['supplier_sku' => 'SS-GLV-A4', 'unit_cost' => 3.20, 'lead_time_days' => 3, 'min_order_quantity' => 50, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 110,
                    $whWest->id => 50,
                    $whEast->id => 40,
                ],
            ],
            [
                'sku' => 'PRD-00008',
                'barcode' => '8901234567897',
                'name' => 'Industrial Pallet Stretch Wrap 18" x 1500ft 80-Gauge',
                'description' => 'Heavy-duty cast stretch film roll. Exceptional puncture resistance and stretch.',
                'category_id' => $catPackaging->id,
                'unit' => 'roll',
                'cost_price' => 11.00,
                'sell_price' => 24.00,
                'reorder_level' => 20,
                'suppliers' => [
                    $supApex->id => ['supplier_sku' => 'APX-WRAP-80G', 'unit_cost' => 11.00, 'lead_time_days' => 2, 'min_order_quantity' => 12, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 40,
                    $whWest->id => 18,
                    $whEast->id => 15,
                ],
            ],
            [
                'sku' => 'PRD-00009',
                'barcode' => '8901234567898',
                'name' => 'Precision Digital Bench & Shipping Scale 50kg / 110lb',
                'description' => 'Stainless steel platform with remote backlit LCD readout. AC adapter or battery powered.',
                'category_id' => $catWarehouse->id,
                'unit' => 'pcs',
                'cost_price' => 28.00,
                'sell_price' => 59.90,
                'reorder_level' => 6,
                'suppliers' => [
                    $supPrecision->id => ['supplier_sku' => 'PRC-SCALE-50', 'unit_cost' => 28.00, 'lead_time_days' => 5, 'min_order_quantity' => 4, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 14,
                    $whWest->id => 6,
                    $whEast->id => 4,
                ],
            ],
            [
                'sku' => 'PRD-00010',
                'barcode' => '8901234567899',
                'name' => '3-Tier Industrial Steel Utility Cart with Swivel Casters',
                'description' => '500 lb load capacity with locking casters. Tough powder-coated blue finish.',
                'category_id' => $catWarehouse->id,
                'unit' => 'pcs',
                'cost_price' => 55.00,
                'sell_price' => 119.00,
                'reorder_level' => 5,
                'suppliers' => [
                    $supPrecision->id => ['supplier_sku' => 'PRC-CART-3T', 'unit_cost' => 55.00, 'lead_time_days' => 8, 'min_order_quantity' => 2, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 8,
                    $whWest->id => 4,
                    $whEast->id => 3,
                ],
            ],
            [
                'sku' => 'PRD-00011',
                'barcode' => '8901234567810',
                'name' => 'Industrial Bluetooth Thermal Barcode Printer (203 DPI)',
                'description' => 'High-speed desktop label printer compatible with 4x6 labels and all ERPs.',
                'category_id' => $catElectronics->id,
                'unit' => 'pcs',
                'cost_price' => 110.00,
                'sell_price' => 229.00,
                'reorder_level' => 6,
                // Intentionally low stock
                'suppliers' => [
                    $supTech->id => ['supplier_sku' => 'TC-PRN-203D', 'unit_cost' => 110.00, 'lead_time_days' => 6, 'min_order_quantity' => 3, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 2, // Low stock!
                    $whWest->id => 1,
                    $whEast->id => 0,
                ],
            ],
            [
                'sku' => 'PRD-00012',
                'barcode' => '8901234567811',
                'name' => 'Wide-Vision Anti-Fog Safety Splash Goggles',
                'description' => 'Direct vent polycarbonate lens with adjustable elastic strap and UV400 protection.',
                'category_id' => $catSafety->id,
                'unit' => 'pcs',
                'cost_price' => 4.20,
                'sell_price' => 9.99,
                'reorder_level' => 25,
                'suppliers' => [
                    $supShield->id => ['supplier_sku' => 'SS-GOGGLE-AF', 'unit_cost' => 4.20, 'lead_time_days' => 4, 'min_order_quantity' => 20, 'is_preferred' => true],
                ],
                'stocks' => [
                    $whMain->id => 55,
                    $whWest->id => 25,
                    $whEast->id => 20,
                ],
            ],
        ];

        $seededProducts = [];

        foreach ($productsData as $data) {
            $product = Product::firstOrCreate(
                ['sku' => $data['sku']],
                [
                    'barcode' => $data['barcode'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'category_id' => $data['category_id'],
                    'unit' => $data['unit'],
                    'cost_price' => $data['cost_price'],
                    'sell_price' => $data['sell_price'],
                    'reorder_level' => $data['reorder_level'],
                    'is_active' => true,
                ],
            );

            // Sync suppliers
            if (! empty($data['suppliers'])) {
                $product->suppliers()->syncWithoutDetaching($data['suppliers']);
            }

            // Sync stock and log initial movements
            foreach ($data['stocks'] as $whId => $qty) {
                $stock = Stock::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $whId],
                    ['quantity' => $qty],
                );

                if ($stock->quantity !== $qty) {
                    $stock->update(['quantity' => $qty]);
                }

                // Create audit movement if none exists
                StockMovement::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $whId,
                        'reference' => 'INITIAL-SEED',
                    ],
                    [
                        'user_id' => $admin->id,
                        'type' => 'in',
                        'quantity' => $qty,
                        'balance_after' => $qty,
                        'note' => 'Initial inventory warehouse opening balance',
                    ],
                );
            }

            $seededProducts[$data['sku']] = $product;
        }

        // Add a sample inter-warehouse transfer movement
        $transferRef = (string) Str::uuid();
        $sampleProd = $seededProducts['PRD-00001'];
        StockMovement::firstOrCreate(
            ['reference' => $transferRef, 'type' => 'transfer_out'],
            [
                'product_id' => $sampleProd->id,
                'warehouse_id' => $whMain->id,
                'user_id' => $manager->id,
                'quantity' => -5,
                'balance_after' => 45,
                'note' => 'Rebalanced stock transfer to West Coast Hub',
            ],
        );
        StockMovement::firstOrCreate(
            ['reference' => $transferRef, 'type' => 'transfer_in'],
            [
                'product_id' => $sampleProd->id,
                'warehouse_id' => $whWest->id,
                'user_id' => $manager->id,
                'quantity' => 5,
                'balance_after' => 20,
                'note' => 'Rebalanced stock transfer from Main Distribution Center',
            ],
        );

        // -------------------------------------------------------------
        // 7. PURCHASE ORDERS
        // -------------------------------------------------------------
        // PO-00001: Fully Received
        $po1 = PurchaseOrder::firstOrCreate(
            ['number' => 'PO-00001'],
            [
                'supplier_id' => $supTech->id,
                'status' => 'received',
                'expected_at' => now()->subDays(6)->toDateString(),
                'received_at' => now()->subDays(4),
                'notes' => 'Q4 replenishment order for barcode scanners and docking stations.',
                'created_by' => $manager->id,
                'total' => 3550.00,
            ],
        );

        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po1->id, 'product_id' => $seededProducts['PRD-00001']->id],
            ['quantity' => 50, 'quantity_received' => 50, 'unit_cost' => 45.00],
        );
        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po1->id, 'product_id' => $seededProducts['PRD-00004']->id],
            ['quantity' => 20, 'quantity_received' => 20, 'unit_cost' => 65.00],
        );

        // PO-00002: Partially Received
        $po2 = PurchaseOrder::firstOrCreate(
            ['number' => 'PO-00002'],
            [
                'supplier_id' => $supApex->id,
                'status' => 'partially_received',
                'expected_at' => now()->subDays(2)->toDateString(),
                'received_at' => now()->subDays(1),
                'notes' => 'Bulk shipment of thermal labels and shipping boxes. Second pallet arriving Friday.',
                'created_by' => $manager->id,
                'total' => 2350.00,
            ],
        );

        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po2->id, 'product_id' => $seededProducts['PRD-00002']->id],
            ['quantity' => 100, 'quantity_received' => 50, 'unit_cost' => 12.50],
        );
        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po2->id, 'product_id' => $seededProducts['PRD-00003']->id],
            ['quantity' => 50, 'quantity_received' => 25, 'unit_cost' => 22.00],
        );

        // PO-00003: Draft PO
        $po3 = PurchaseOrder::firstOrCreate(
            ['number' => 'PO-00003'],
            [
                'supplier_id' => $supShield->id,
                'status' => 'draft',
                'expected_at' => now()->addDays(5)->toDateString(),
                'notes' => 'Scheduled safety equipment restock for spring onboarding.',
                'created_by' => $staff->id,
                'total' => 645.00,
            ],
        );

        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po3->id, 'product_id' => $seededProducts['PRD-00006']->id],
            ['quantity' => 50, 'quantity_received' => 0, 'unit_cost' => 6.50],
        );
        PurchaseOrderItem::firstOrCreate(
            ['purchase_order_id' => $po3->id, 'product_id' => $seededProducts['PRD-00007']->id],
            ['quantity' => 100, 'quantity_received' => 0, 'unit_cost' => 3.20],
        );

        // -------------------------------------------------------------
        // 8. SALES ORDERS
        // -------------------------------------------------------------
        // SO-00001: Shipped
        $so1 = SalesOrder::firstOrCreate(
            ['number' => 'SO-00001'],
            [
                'customer_id' => $custAcme->id,
                'status' => 'shipped',
                'order_date' => now()->subDays(4)->toDateString(),
                'expected_ship_date' => now()->subDays(2)->toDateString(),
                'shipped_at' => now()->subDays(2),
                'shipping_address' => "Acme Distribution Dock #4\n742 Evergreen Terrace\nSpringfield, OR 97477",
                'notes' => 'Direct to warehouse receiving dock. Liftgate delivery required.',
                'created_by' => $manager->id,
                'subtotal' => 699.85,
                'tax' => 55.99,
                'discount' => 0.00,
                'total' => 755.84,
            ],
        );

        SalesOrderItem::firstOrCreate(
            ['sales_order_id' => $so1->id, 'product_id' => $seededProducts['PRD-00001']->id],
            [
                'quantity' => 5,
                'quantity_shipped' => 5,
                'unit_price' => 89.99,
                'line_total' => 449.95,
            ],
        );
        SalesOrderItem::firstOrCreate(
            ['sales_order_id' => $so1->id, 'product_id' => $seededProducts['PRD-00002']->id],
            [
                'quantity' => 10,
                'quantity_shipped' => 10,
                'unit_price' => 24.99,
                'line_total' => 249.90,
            ],
        );

        // SO-00002: Partially Shipped
        $so2 = SalesOrder::firstOrCreate(
            ['number' => 'SO-00002'],
            [
                'customer_id' => $custStark->id,
                'status' => 'partially_shipped',
                'order_date' => now()->subDays(2)->toDateString(),
                'expected_ship_date' => now()->addDays(1)->toDateString(),
                'shipped_at' => now()->subDay(),
                'shipping_address' => "Stark Industries Hangar 3\n10880 Malibu Point\nMalibu, CA 90265",
                'notes' => 'Expedited priority shipment. Balance of docks shipping upon arrival.',
                'created_by' => $staff->id,
                'subtotal' => 649.95,
                'tax' => 52.00,
                'discount' => 25.00,
                'total' => 676.95,
            ],
        );

        SalesOrderItem::firstOrCreate(
            ['sales_order_id' => $so2->id, 'product_id' => $seededProducts['PRD-00004']->id],
            [
                'quantity' => 5,
                'quantity_shipped' => 3,
                'unit_price' => 129.99,
                'line_total' => 649.95,
            ],
        );

        // SO-00003: Confirmed (Ready to dispatch)
        $so3 = SalesOrder::firstOrCreate(
            ['number' => 'SO-00003'],
            [
                'customer_id' => $custWayne->id,
                'status' => 'confirmed',
                'order_date' => now()->subDay()->toDateString(),
                'expected_ship_date' => now()->addDays(2)->toDateString(),
                'shipping_address' => "Wayne Enterprises Central Facility\n1007 Mountain Drive\nGotham, NJ 07001",
                'notes' => 'Deliver during business hours 8am-4pm.',
                'created_by' => $manager->id,
                'subtotal' => 357.00,
                'tax' => 28.56,
                'discount' => 0.00,
                'total' => 385.56,
            ],
        );

        SalesOrderItem::firstOrCreate(
            ['sales_order_id' => $so3->id, 'product_id' => $seededProducts['PRD-00010']->id],
            [
                'quantity' => 3,
                'quantity_shipped' => 0,
                'unit_price' => 119.00,
                'line_total' => 357.00,
            ],
        );

        // SO-00004: Draft
        $so4 = SalesOrder::firstOrCreate(
            ['number' => 'SO-00004'],
            [
                'customer_id' => $custInitech->id,
                'status' => 'draft',
                'order_date' => now()->toDateString(),
                'expected_ship_date' => now()->addDays(4)->toDateString(),
                'shipping_address' => "Initech Operations Building B\n4120 Freemont Ave\nAustin, TX 78701",
                'notes' => 'Customer reviewing quote for anti-fatigue floor mats.',
                'created_by' => $admin->id,
                'subtotal' => 194.85,
                'tax' => 15.59,
                'discount' => 0.00,
                'total' => 210.44,
            ],
        );

        SalesOrderItem::firstOrCreate(
            ['sales_order_id' => $so4->id, 'product_id' => $seededProducts['PRD-00005']->id],
            [
                'quantity' => 3,
                'quantity_shipped' => 0,
                'unit_price' => 64.95,
                'line_total' => 194.85,
            ],
        );

        // -------------------------------------------------------------
        // 9. AUDIT ACTIVITY LOGS
        // -------------------------------------------------------------
        ActivityLog::firstOrCreate(
            ['action' => 'shipped', 'subject_type' => 'sales_order', 'subject_id' => $so1->id],
            [
                'user_id' => $manager->id,
                'user_name' => $manager->name,
                'description' => "Dispatched and shipped order {$so1->number} to {$custAcme->company_name}",
                'properties' => ['warehouse' => $whMain->name, 'carrier' => 'FedEx Freight'],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(2),
            ],
        );

        ActivityLog::firstOrCreate(
            ['action' => 'received', 'subject_type' => 'purchase_order', 'subject_id' => $po1->id],
            [
                'user_id' => $manager->id,
                'user_name' => $manager->name,
                'description' => "Received and stocked line items on {$po1->number} from {$supTech->name}",
                'properties' => ['warehouse' => $whMain->name, 'items_count' => 2],
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(4),
            ],
        );
    }
}
