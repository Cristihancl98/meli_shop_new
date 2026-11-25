<?php

namespace Database\Seeders;

use App\DTOs\CreateStoreDTO;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MercadolibreAccount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PostSaleMessage;
use App\Models\Product;
use App\Models\Question;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreProvisioningService;
use App\Services\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tienda de demostración SOLO para desarrollo local (usa factories y datos ficticios).
 * php artisan db:seed --class=DemoStoreSeeder
 */
class DemoStoreSeeder extends Seeder
{
    public const CODE           = 'DEMO';
    public const DATABASE       = 'meli_store_demo';
    public const ADMIN_EMAIL    = 'admin@demo.local';
    public const OPERATOR_EMAIL = 'operador@demo.local';
    public const PASSWORD       = 'Demo2026!';

    private const CATALOG = [
        ['MCO1367', 'Taladro Percutor Inalámbrico 20V Con 2 Baterías', 389900, 'BOSCH-GSB20'],
        ['MCO1367', 'Pulidora Angular 4-1/2 Pulgadas 850W', 219900, 'DEW-DWE4120'],
        ['MCO1367', 'Juego De Llaves Mixtas 12 Piezas Cromo Vanadio', 89900, 'STAN-LLX12'],
        ['MCO1367', 'Caja De Herramientas Plástica 19 Pulgadas', 64900, 'TRUP-CJ19'],
        ['MCO1367', 'Multímetro Digital Automático Con Pinza', 129900, 'UNIT-UT210E'],
        ['MCO1051', 'Portátil 15.6 Pulgadas Ryzen 5 16GB RAM 512GB SSD', 2499900, 'LEN-IP3-R5'],
        ['MCO1051', 'Monitor Gamer 27 Pulgadas 165Hz IPS', 1149900, 'SAM-ODY27'],
        ['MCO1051', 'Teclado Mecánico RGB Switch Red Español', 189900, 'RDG-K552'],
        ['MCO1051', 'Mouse Inalámbrico Ergonómico Recargable', 79900, 'LOG-M720'],
        ['MCO1051', 'Disco Sólido SSD NVMe 1TB', 299900, 'KNG-NV2-1T'],
        ['MCO1648', 'Celular 128GB 8GB RAM Cámara 108MP', 1299900, 'XIA-RN13'],
        ['MCO1648', 'Cargador Rápido USB-C 65W GaN', 119900, 'ANK-65W'],
        ['MCO1648', 'Forro Antichoque Transparente Con Protector', 34900, 'GEN-FORRO'],
        ['MCO1000', 'Audífonos Bluetooth Cancelación De Ruido', 349900, 'SNY-WHCH720'],
        ['MCO1000', 'Parlante Portátil Bluetooth Resistente Al Agua', 249900, 'JBL-FLIP6'],
        ['MCO1000', 'Smart TV 50 Pulgadas 4K UHD Google TV', 1899900, 'TCL-50P745'],
        ['MCO1000', 'Barra De Sonido 2.1 Con Subwoofer 300W', 699900, 'LG-SN5Y'],
        ['MCO1246', 'Juego De Sábanas Doble 400 Hilos Algodón', 159900, 'PRN-SAB400'],
        ['MCO1246', 'Olla De Presión 6 Litros Acero Inoxidable', 189900, 'IMU-OP6'],
        ['MCO1246', 'Silla De Oficina Ergonómica Malla Negra', 459900, 'ERG-SM01'],
        ['MCO1246', 'Lámpara De Escritorio LED Recargable', 69900, 'LUX-LED10'],
        ['MCO1953', 'Bicicleta Estática Magnética Con Monitor', 1199900, 'ATH-BE200'],
        ['MCO1953', 'Set De Mancuernas Ajustables 20kg', 279900, 'FIT-MAN20'],
        ['MCO1953', 'Colchoneta Yoga 6mm Antideslizante', 59900, 'YOG-MAT6'],
        ['MCO1276', 'Secador De Pelo Iónico 2200W', 189900, 'REM-D3190'],
        ['MCO1276', 'Afeitadora Eléctrica Recargable 3 Cabezales', 229900, 'PHL-S1323'],
        ['MCO1574', 'Control Inalámbrico Para Consola Negro', 289900, 'SNY-DS5'],
        ['MCO1574', 'Tarjeta Regalo Tienda De Juegos 50 USD', 219900, 'GFT-50USD'],
        ['MCO1743', 'Cama Para Perro Ortopédica Talla L', 149900, 'PET-CAMA-L'],
        ['MCO1743', 'Comedero Automático Para Mascotas 4L', 199900, 'PET-COM4L'],
    ];

    private const QUESTIONS = [
        '¿Tiene garantía y por cuánto tiempo?',
        '¿Hacen envíos a Medellín? ¿Cuánto se demora?',
        '¿Incluye factura electrónica?',
        '¿El precio es por unidad?',
        '¿Viene con cargador incluido?',
        'Buenas tardes, ¿tienen disponible en color blanco?',
        '¿Puedo pagar contra entrega?',
        '¿Es original? ¿Trae sellos de seguridad?',
    ];

    private const MESSAGES = [
        'Hola, ¿ya despacharon mi pedido?',
        'Buen día, ¿me pueden enviar la factura?',
        'El producto llegó perfecto, gracias.',
        '¿Cuál es el número de guía?',
    ];

    public function run(StoreProvisioningService $provisioning, TenantManager $tenantManager): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoStoreSeeder es solo para desarrollo local: no se ejecuta con APP_ENV=production.');

            return;
        }

        $store =Store::where('connection_code', self::CODE)->first() ?? $provisioning->provision(new CreateStoreDTO(
            name:           'Tienda Demo',
            connectionCode: self::CODE,
            database:       self::DATABASE,
            adminName:      'Admin Demo',
            adminEmail:     self::ADMIN_EMAIL,
            adminPassword:  self::PASSWORD,
        ));

        $tenantManager->runFor($store, function () {
            mt_srand(2026);

            $account  = $this->seedUsersAndAccount();
            $products = $this->seedProducts($account);
            $this->seedOrders($account, $products);
            $this->seedConversations($account, $products);
        });

        $this->command?->info('Tienda demo lista: código ' . self::CODE . ', usuarios ' . self::ADMIN_EMAIL . ' / ' . self::OPERATOR_EMAIL . ' (clave en DemoStoreSeeder::PASSWORD).');
    }

    private function seedUsersAndAccount(): MercadolibreAccount
    {
        $admin = User::firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            ['name' => 'Admin Demo', 'password' => self::PASSWORD, 'email_verified_at' => now()]
        );
        $admin->syncRole('admin');

        $operator = User::firstOrCreate(
            ['email' => self::OPERATOR_EMAIL],
            ['name' => 'Operador Demo', 'password' => self::PASSWORD, 'email_verified_at' => now()]
        );
        $operator->syncRole('operator');

        return MercadolibreAccount::updateOrCreate(
            ['meli_user_id' => '900000001'],
            [
                'user_id'       => $admin->id,
                'access_token'  => 'APP_USR-DEMO-FICTICIO',
                'refresh_token' => 'TG-DEMO-FICTICIO',
                'expires_at'    => now()->addYear(),
                'nickname'      => 'TIENDADEMO.CO',
                'email'         => 'ventas@demo.local',
                'is_active'     => true,
            ]
        );
    }

    private function seedProducts(MercadolibreAccount $account): Collection
    {
        $categories = Category::pluck('id', 'meli_category_id');

        return collect(self::CATALOG)->map(function (array $row, int $i) use ($account, $categories) {
            [$meliCategory, $title, $price, $sku] = $row;
            $itemId = 'MCO' . (1400000000 + $i * 7919);

            return Product::updateOrCreate(['sku' => $sku], [
                'meli_item_id'            => $itemId,
                'mercadolibre_account_id' => $account->id,
                'category_id'             => $categories[$meliCategory] ?? null,
                'meli_category_id'        => $meliCategory,
                'title'                   => $title,
                'description'             => "{$title}. Producto nuevo con garantía de 6 meses. Envío a todo Colombia. Factura electrónica disponible.",
                'price'                   => $price,
                'base_price'              => round($price * 0.62, 2),
                'stock'                   => $i % 6 === 0 ? mt_rand(0, 3) : mt_rand(8, 120),
                'status'                  => match (true) { $i % 10 === 7 => 'paused', $i % 15 === 14 => 'closed', default => 'active' },
                'condition'               => 'new',
                'listing_type_id'         => $i % 3 === 0 ? 'gold_pro' : 'gold_special',
                'thumbnail'               => 'https://placehold.co/300x300/png?text=' . rawurlencode(strtok($title, ' ')),
                'permalink'               => "https://articulo.mercadolibre.com.co/{$itemId}-demo",
                'last_sync'               => now(),
                'published_at'            => now()->subDays(mt_rand(5, 200)),
            ]);
        });
    }

    private function seedOrders(MercadolibreAccount $account, Collection $products): void
    {
        $customers = collect(range(1, 25))->map(fn (int $n) => Customer::firstOrCreate(
            ['meli_customer_id' => 'DEMO-' . $n],
            Customer::factory()->raw(['mercadolibre_account_id' => $account->id, 'meli_customer_id' => 'DEMO-' . $n])
        ));

        $sellable = $products->where('status', '!=', 'closed')->values();
        $statuses = ['paid', 'paid', 'paid', 'delivered', 'delivered', 'pending', 'cancelled'];

        for ($n = 0; $n < 70; $n++) {
            $status = $statuses[array_rand($statuses)];
            $picked = $sellable->random(mt_rand(1, 3))->map(fn (Product $p) => [$p, mt_rand(1, 2)]);

            $order = Order::updateOrCreate(['meli_order_id' => (string) (2000010000000 + $n)], [
                'mercadolibre_account_id' => $account->id,
                'customer_id'             => $customers->random()->id,
                'status'                  => $status,
                'payment_status'          => match ($status) { 'pending' => 'pending', 'cancelled' => 'rejected', default => 'approved' },
                'shipping_status'         => match ($status) { 'delivered' => 'delivered', 'paid' => 'ready_to_ship', default => 'not_delivered' },
                'total_amount'            => $picked->sum(fn ($item) => $item[0]->price * $item[1]),
                'order_date'              => Carbon::now()->subDays(mt_rand(0, 180))->setTime(mt_rand(7, 22), mt_rand(0, 59)),
            ]);

            OrderItem::where('order_id', $order->id)->delete();

            foreach ($picked as [$product, $quantity]) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'meli_item_id' => $product->meli_item_id,
                    'title'        => $product->title,
                    'quantity'     => $quantity,
                    'unit_price'   => $product->price,
                    'total_price'  => $product->price * $quantity,
                ]);
            }
        }
    }

    private function seedConversations(MercadolibreAccount $account, Collection $products): void
    {
        $sellable = $products->where('status', 'active')->values();

        foreach (self::QUESTIONS as $i => $text) {
            $factory = $i < 3 ? Question::factory()->answered() : Question::factory();

            Question::updateOrCreate(['meli_question_id' => 'DEMO-Q' . $i], $factory->raw([
                'meli_question_id'        => 'DEMO-Q' . $i,
                'mercadolibre_account_id' => $account->id,
                'meli_item_id'            => $sellable[$i * 3 % $sellable->count()]->meli_item_id,
                'question'                => $text,
                'asked_at'                => now()->subHours($i * 5 + 1),
            ]));
        }

        Order::where('status', 'paid')->latest('order_date')->take(count(self::MESSAGES))->get()
            ->each(fn (Order $order, int $i) => PostSaleMessage::updateOrCreate(
                ['meli_message_id' => 'demo-msg-' . $i],
                PostSaleMessage::factory()->raw([
                    'meli_message_id'         => 'demo-msg-' . $i,
                    'mercadolibre_account_id' => $account->id,
                    'pack_id'                 => $order->meli_order_id,
                    'text'                    => self::MESSAGES[$i],
                    'sent_at'                 => now()->subHours($i * 3 + 2),
                ])
            ));
    }
}
