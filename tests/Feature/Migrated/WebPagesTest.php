<?php

namespace Tests\Feature\Migrated;

use App\Models\BulkPublishCode;
use App\Models\Customer;
use App\Models\MeliNotification;
use App\Models\Order;
use App\Models\PostSaleMessage;
use App\Models\Product;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    public function test_admin_can_render_every_migrated_page(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order   = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'shipping_id' => '44']);
        $product = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id]);
        Question::factory()->create(['mercadolibre_account_id' => $account->id]);
        PostSaleMessage::factory()->create(['mercadolibre_account_id' => $account->id, 'order_id' => $order->id]);
        MeliNotification::factory()->create(['mercadolibre_account_id' => $account->id]);
        BulkPublishCode::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->actingAs($admin);

        foreach ([
            route('settings.edit')     => 'Configuración de la tienda',
            route('publisher.create')  => 'Consulta un SKU',
            route('publisher.catalog') => 'Catálogo masivo',
            route('publisher.codes')   => 'Códigos pendientes',
            route('questions.index')   => 'Preguntas preventa',
            route('post-sale.index')   => 'Mensajería posventa',
            route('notifications.index') => 'sin leer',
            route('users.index')       => 'Registrar usuario',
            route('users.edit', $admin->id) => 'Editar usuario',
            route('products.show', $product->id) => 'Editar publicación en Mercado Libre',
            route('orders.show', $order->id) => 'Descargar etiqueta de envío',
        ] as $url => $text) {
            $this->get($url)->assertOk()->assertSee($text, false);
        }
    }

    public function test_settings_form_is_editable_without_meli_account(): void
    {
        $this->actingAs($this->createAdmin());

        $this->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Guardar configuración')
            ->assertSee('name="dollar_price"', false)
            ->assertSee('value="5000"', false);

        $this->put(route('settings.update'), ['dollar_price' => '4300'])->assertRedirect(route('settings.edit'));
        $this->get(route('settings.edit'))->assertSee('value="4300"', false);
    }

    public function test_operator_sees_read_only_settings_and_no_admin_pages(): void
    {
        [$operator] = $this->operatorWithAccount();
        $this->actingAs($operator);

        $this->get(route('settings.edit'))->assertOk()->assertDontSee('Guardar configuración');
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('publisher.create'))->assertForbidden();
        $this->put(route('settings.update'), ['iva' => 5])->assertForbidden();
    }

    public function test_settings_form_saves_ranges_and_ignores_blank_rows(): void
    {
        [$admin, $account] = $this->adminWithAccount();

        $this->actingAs($admin)->put(route('settings.update'), [
            'iva'                  => '19',
            'sale_message_enabled' => '1',
            'sale_message'         => 'Gracias',
            'weight_ranges'        => [
                ['from' => '0', 'to' => '5', 'price' => '4'],
                ['from' => '', 'to' => '', 'price' => ''],
            ],
        ])->assertRedirect(route('settings.edit'));

        $this->get(route('settings.edit'))->assertSee('value="4"', false);
        $this->assertSame([['from' => '0', 'to' => '5', 'price' => '4']], \App\Models\StoreSetting::where('key', 'weight_ranges')->first()->typedValue());
    }

    public function test_publisher_lookup_shows_price_and_suggested_category(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $this->setSettings(['scraping_url' => 'https://scraper.test', 'dollar_price' => 4000]);

        Http::fake([
            'scraper.test/*' => Http::response(['titulo' => 'Reloj inteligente', 'precio' => 50, 'peso' => 1, 'imagenes' => ['https://img.test/r.jpg']]),
            'api.mercadolibre.com/sites/MCO/domain_discovery/search*' => Http::response([['category_id' => 'MCO5555']]),
        ]);

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0WATCH']))
            ->assertOk()
            ->assertSee('Reloj inteligente')
            ->assertSee('MCO5555')
            ->assertSee('308.000');
    }

    public function test_publisher_lookup_uses_catalog_category_and_attributes(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [[
            'titulo'        => 'Mouse inalámbrico HP',
            'tituloMeli'    => 'Mouse Inalámbrico Hp X3000',
            'imagenes'      => ['https://img.test/m.jpg'],
            'atributos'     => ['marca' => 'HP', 'modelo' => 'X3000', 'atributosExtra' => [['id' => 'COLOR', 'name' => 'Color', 'value_name' => 'Negro']]],
            'precio'        => 12,
            'peso'          => 1,
            'sku'           => 'B08NM2GF2V',
            'categoriaMeli' => 'MCO1714',
        ]];
        Http::fake();

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B08NM2GF2V']))
            ->assertOk()
            ->assertSee('Producto encontrado en el catálogo (MongoDB).')
            ->assertSee('value="Mouse Inalámbrico Hp X3000"', false)
            ->assertSee('value="MCO1714"', false)
            ->assertSee('Categoría del catálogo')
            ->assertSee('value="COLOR"', false)
            ->assertSee('value="Negro"', false);

        Http::assertNothingSent();
    }

    public function test_publisher_store_publishes_and_redirects_to_product(): void
    {
        [$admin] = $this->adminWithAccount();

        Http::fake([
            'api.mercadolibre.com/items/MCO321/description' => Http::response(['text' => 'ok']),
            'api.mercadolibre.com/items'                    => Http::response(['id' => 'MCO321']),
        ]);

        $response = $this->actingAs($admin)->post(route('publisher.store'), [
            'sku' => 'B0WEB', 'title' => 'Producto web', 'meli_category_id' => 'MCO1', 'final_price' => 100000,
            'base_price' => 20, 'quantity' => 1, 'weight' => 1, 'brand' => 'Genérica',
            'pictures' => "https://img.test/a.jpg\nhttps://img.test/b.jpg",
        ]);

        $product = Product::where('sku', 'B0WEB')->firstOrFail();
        $response->assertRedirect(route('products.show', $product->id));
        $this->assertSame(['https://img.test/a.jpg', 'https://img.test/b.jpg'], $product->pictures);
    }

    public function test_publisher_keeps_picture_order_and_drops_duplicates(): void
    {
        [$admin] = $this->adminWithAccount();

        Http::fake([
            'api.mercadolibre.com/items/MCO777/description' => Http::response(['text' => 'ok']),
            'api.mercadolibre.com/items'                    => Http::response(['id' => 'MCO777']),
        ]);

        $this->actingAs($admin)->post(route('publisher.store'), [
            'sku' => 'B0ORDER', 'title' => 'Orden de imágenes', 'meli_category_id' => 'MCO1', 'final_price' => 100000,
            'base_price' => 20, 'quantity' => 1, 'weight' => 1, 'brand' => 'Genérica',
            'pictures' => ['https://img.test/c.jpg', 'https://img.test/a.jpg', ' https://img.test/c.jpg ', 'https://img.test/b.jpg'],
        ])->assertRedirect();

        $expected = ['https://img.test/c.jpg', 'https://img.test/a.jpg', 'https://img.test/b.jpg'];
        $this->assertSame($expected, Product::where('sku', 'B0ORDER')->value('pictures'));
        $this->assertSame('https://img.test/c.jpg', Product::where('sku', 'B0ORDER')->value('thumbnail'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadolibre.com/items'
            && array_column($request['pictures'], 'source') === $expected);
    }

    public function test_publisher_recalculates_price_from_usd(): void
    {
        $this->setSettings(['dollar_price' => 4000]);
        $this->actingAs($this->createAdmin());

        $this->getJson(route('publisher.price', ['base_price' => 100, 'weight' => 2]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.base_price', 100)
            ->assertJsonPath('data.final_price', 563000)
            ->assertJsonStructure(['data' => ['iva', 'profit', 'meli_commission', 'subtotal_usd', 'dollar_price']]);

        $this->getJson(route('publisher.price', ['base_price' => 150, 'weight' => 2]))
            ->assertOk()
            ->assertJsonPath('data.final_price', 819000);
    }

    public function test_price_recalculation_validates_and_requires_permission(): void
    {
        $this->actingAs($this->createAdmin());
        $this->getJson(route('publisher.price', ['base_price' => -5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['base_price', 'weight']);

        $this->actingAs($this->createOperator());
        $this->getJson(route('publisher.price', ['base_price' => 10, 'weight' => 1]))->assertForbidden();
    }

    public function test_publisher_form_renders_picture_editor(): void
    {
        [$admin] = $this->adminWithAccount();
        $this->catalog->products = [[
            'titulo' => 'Lámpara', 'precio' => 10, 'peso' => 1, 'sku' => 'B0LAMP',
            'imagenes' => ['https://img.test/1.jpg', 'https://img.test/2.jpg'],
        ]];

        $this->actingAs($admin)->get(route('publisher.create', ['sku' => 'B0LAMP']))
            ->assertOk()
            ->assertSee('class="picture-editor"', false)
            ->assertSee('name="pictures[]" value="https://img.test/1.jpg"', false)
            ->assertSee('name="pictures[]" value="https://img.test/2.jpg"', false)
            ->assertSee('id="publisher-preview"', false)
            ->assertSee('href="https://ca.camelcamelcamel.com/product/B0LAMP"', false)
            ->assertSee('href="https://www.amazon.com/dp/B0LAMP"', false)
            ->assertSee('name="base_price" id="price-base"', false)
            ->assertSee('id="price-recalculate"', false);
    }

    public function test_publisher_store_shows_meli_error_on_form(): void
    {
        [$admin] = $this->adminWithAccount();
        Http::fake(['api.mercadolibre.com/items' => Http::response(['message' => 'category invalid'], 400)]);

        $this->actingAs($admin)->from(route('publisher.create'))->post(route('publisher.store'), [
            'sku' => 'B0ERR', 'title' => 'X', 'meli_category_id' => 'MCO1', 'final_price' => 1000,
            'base_price' => 1, 'quantity' => 1, 'weight' => 1, 'brand' => 'X', 'pictures' => 'https://img.test/a.jpg',
        ])->assertRedirect(route('publisher.create'))->assertSessionHasErrors('general');
    }

    public function test_codes_form_registers_codes(): void
    {
        [$admin, $account] = $this->adminWithAccount();

        $this->actingAs($admin)->post(route('publisher.codes.store'), ['codes' => "A1\nA2"])
            ->assertRedirect(route('publisher.codes'));

        $this->assertSame(2, BulkPublishCode::where('mercadolibre_account_id', $account->id)->count());
    }

    public function test_question_answer_and_seen_from_web(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $question = Question::factory()->create(['mercadolibre_account_id' => $account->id]);
        Http::fake(['api.mercadolibre.com/answers' => Http::response(['id' => 1, 'answer' => ['text' => 'Hola', 'status' => 'ACTIVE']])]);

        $this->actingAs($admin)->patch(route('questions.seen', $question->id))->assertRedirect();
        $this->post(route('questions.answer', $question->id), ['answer' => 'Hola'])->assertRedirect();

        $this->assertTrue($question->fresh()->seen);
        $this->assertSame('Hola', $question->fresh()->answer);
    }

    public function test_post_sale_reply_from_web(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $customer = Customer::factory()->create(['mercadolibre_account_id' => $account->id]);
        $order    = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'customer_id' => $customer->id]);
        Http::fake(['api.mercadolibre.com/messages/packs/*' => Http::response(['id' => 'w1'])]);

        $this->actingAs($admin)->post(route('post-sale.reply', $order->id), ['text' => 'Enviado'])->assertRedirect();

        $this->assertDatabaseHas('post_sale_messages', ['order_id' => $order->id, 'text' => 'Enviado']);
    }

    public function test_product_listing_actions_from_web(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $product = Product::factory()->active()->create(['mercadolibre_account_id' => $account->id, 'meli_item_id' => 'MCO5']);
        Http::fake(['api.mercadolibre.com/items/MCO5' => Http::response(['id' => 'MCO5', 'status' => 'paused', 'last_updated' => 'now'])]);

        $this->actingAs($admin)->post(route('products.pause', $product->id))->assertRedirect();
        $this->assertSame('paused', $product->fresh()->status);

        $this->patch(route('products.listing', $product->id), ['title' => 'Editado', 'pictures' => ''])
            ->assertRedirect(route('products.show', $product->id));
        $this->assertSame('Editado', $product->fresh()->title);

        $this->post(route('products.archive', $product->id))->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_order_final_price_and_label_from_web(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $order = Order::factory()->create(['mercadolibre_account_id' => $account->id, 'shipping_id' => '77']);
        Http::fake(['api.mercadolibre.com/shipment_labels*' => Http::response('%PDF', 200)]);

        $this->actingAs($admin)->put(route('orders.final-price', $order->id), ['final_price' => 5000])->assertRedirect();
        $this->assertEquals(5000, $order->fresh()->final_price);

        $this->get(route('orders.shipping-label', $order->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_users_create_and_update_from_web(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Web User', 'email' => 'web@example.com', 'password' => 'password123', 'role' => 'operator',
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'web@example.com')->firstOrFail();

        $this->put(route('users.update', $user->id), ['name' => 'Renombrado', 'email' => 'web@example.com', 'password' => '', 'role' => 'operator'])
            ->assertRedirect(route('users.index'));

        $this->assertSame('Renombrado', $user->fresh()->name);
    }

    public function test_notification_mark_as_read_from_web(): void
    {
        [$admin, $account] = $this->adminWithAccount();
        $notification = MeliNotification::factory()->create(['mercadolibre_account_id' => $account->id]);

        $this->actingAs($admin)->patch(route('notifications.read', $notification->id))->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
