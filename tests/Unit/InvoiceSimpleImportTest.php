<?php

use App\Models\Invoice;
use App\Models\SimpleInvoice;
use App\Models\User;
use App\Services\InvoiceSimpleImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(Tests\TestCase::class);

beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->unsignedBigInteger('role_id')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->string('numero')->index();
        $table->string('codigo')->nullable();
        $table->string('refpago')->unique();
        $table->unsignedBigInteger('valfactura');
        $table->date('fecha')->nullable();
        $table->string('nombre')->nullable();
        $table->string('direccion')->nullable();
        $table->string('status')->default('pendiente');
        $table->text('payment_link_url')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->string('wompi_reference')->nullable();
        $table->string('wompi_link_id')->nullable();
        $table->string('wompi_transaction_id')->nullable();
        $table->string('wompi_status')->nullable();
        $table->unsignedBigInteger('wompi_amount_in_cents')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });

    Schema::create('simple_invoices', function (Blueprint $table) {
        $table->id();
        $table->string('numero')->index();
        $table->string('codigo')->index();
        $table->string('refpago')->unique();
        $table->unsignedBigInteger('valfactura');
        $table->date('fecha');
        $table->string('nombre');
        $table->string('direccion')->nullable();
        $table->string('status')->default('pendiente');
        $table->text('payment_link_url')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->string('wompi_reference')->nullable();
        $table->string('wompi_link_id')->nullable();
        $table->string('wompi_transaction_id')->nullable();
        $table->string('wompi_status')->nullable();
        $table->unsignedBigInteger('wompi_amount_in_cents')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });

    Cache::flush();
});

afterEach(function () {
    Schema::dropAllTables();
    Cache::flush();
});

function simpleImportUser(int $roleId = 2): User
{
    return User::create([
        'name' => 'Usuario importador',
        'email' => "importador{$roleId}@example.com",
        'password' => 'password',
        'role_id' => $roleId,
    ]);
}

function simpleImportCsv(string $referenceHeader = 'REFERNCIA', string $rows = ''): UploadedFile
{
    $content = "CODIGO;NOMBRE;{$referenceHeader};VALOR\n"
        .($rows !== '' ? $rows : "00123;Acueducto Central;6761067;40.150\n");

    return UploadedFile::fake()->createWithContent('facturas.csv', $content);
}

function simpleImportXlsx(): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['CODIGO', 'NOMBRE', 'REFERNCIA', 'VALOR'],
        ['7811036', 'Usuario XLSX', '9001002', 40150],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'invoice-simple-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile(
        $path,
        'facturas.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

function simpleImportXls(): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['CODIGO', 'NOMBRE', 'REFERNCIA', 'VALOR'],
        ['7811037', 'Usuario XLS', '9001003', 50200],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'invoice-simple-').'.xls';
    (new Xls($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return new UploadedFile($path, 'facturas.xls', 'application/vnd.ms-excel', null, true);
}

test('simple import routes require authentication and contracting permission', function () {
    $this->get(route('invoices.simple-import.form'))
        ->assertRedirect(route('login'));

    $this->actingAs(simpleImportUser(1))
        ->get(route('invoices.simple-import.form'))
        ->assertForbidden();

    $this->actingAs(simpleImportUser())
        ->get(route('invoices.simple-import.form'))
        ->assertOk()
        ->assertViewIs('invoices.import-simple');
});

test('preview reads CSV aliases without saving invoices', function (string $header) {
    $response = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv($header),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    $response->assertOk()
        ->assertViewIs('invoices.import-simple-preview');

    expect($response->viewData('summary')['new'])->toBe(1)
        ->and($response->viewData('rows')[0]['refpago'])->toBe('6761067')
        ->and(SimpleInvoice::count())->toBe(0);
})->with(['REFERNCIA', 'REFERENCIA', 'REFPAGO', 'referencia_de_pago' => 'referencia']);

test('preview reads XLSX files', function () {
    $response = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportXlsx(),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    $response->assertOk();

    expect($response->viewData('summary')['new'])->toBe(1)
        ->and($response->viewData('rows')[0]['codigo'])->toBe('7811036');
});

test('preview reads XLS files', function () {
    $response = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportXls(),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    $response->assertOk();

    expect($response->viewData('summary')['new'])->toBe(1)
        ->and($response->viewData('rows')[0]['codigo'])->toBe('7811037');
});

test('confirmation creates a normalized invoice that is available publicly', function () {
    $date = now()->addDays(5)->toDateString();
    $preview = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv(),
            'fecha_limite' => $date,
        ]);

    $confirm = $this->post(route('invoices.simple-import.confirm'), [
        'token' => $preview->viewData('token'),
    ]);

    $confirm->assertOk()
        ->assertViewIs('invoices.import-simple-result');

    $invoice = SimpleInvoice::where('refpago', '6761067')->firstOrFail();

    expect($invoice->numero)->toBe('00123')
        ->and($invoice->codigo)->toBe('00123')
        ->and($invoice->nombre)->toBe('Acueducto Central')
        ->and((int) $invoice->valfactura)->toBe(40150)
        ->and($invoice->fecha->format('Y-m-d'))->toBe($date)
        ->and($invoice->status)->toBe('pendiente')
        ->and($invoice->wompi_reference)->toBeNull()
        ->and($invoice->paid_at)->toBeNull();

    $this->get(route('pago.search', ['refpago' => '6761067']))
        ->assertRedirect(route('pago.show', ['refpago' => '6761067']));

    $this->get(route('pago.show', ['refpago' => '6761067']))
        ->assertOk()
        ->assertSee('Pagar ahora')
        ->assertViewHas('invoice', fn (SimpleInvoice $found) => $found->is($invoice));
});

test('a traditional invoice with the same reference does not block the simplified invoice', function () {
    $traditional = Invoice::create([
        'numero' => '7504928',
        'codigo' => '14405762',
        'refpago' => '6761067',
        'valfactura' => 15650,
        'fecha' => now()->subDays(3),
        'nombre' => 'Factura tradicional',
        'status' => 'pendiente',
    ]);
    $original = $traditional->fresh()->getAttributes();

    $preview = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv(),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    expect($preview->viewData('summary')['new'])->toBe(1);

    $this->post(route('invoices.simple-import.confirm'), [
        'token' => $preview->viewData('token'),
    ])->assertOk();

    expect(SimpleInvoice::where('refpago', '6761067')->value('valfactura'))->toBe(40150)
        ->and($traditional->fresh()->getAttributes())->toBe($original)
        ->and(Invoice::count())->toBe(1)
        ->and(SimpleInvoice::count())->toBe(1);

    $this->get(route('pago.show', ['refpago' => '6761067']))
        ->assertOk()
        ->assertViewHas(
            'invoice',
            fn (SimpleInvoice $found) => $found->refpago === '6761067'
                && (int) $found->valfactura === 40150
        );
});

test('existing and paid invoices are never modified', function () {
    $paidAt = now()->subDay();
    $existing = SimpleInvoice::create([
        'numero' => 'ORIGINAL',
        'codigo' => 'ORIGINAL',
        'refpago' => '6761067',
        'valfactura' => 99000,
        'fecha' => now()->addDays(10),
        'nombre' => 'Nombre original',
        'status' => 'pagada',
        'payment_link_url' => 'https://checkout.wompi.co/p/original',
        'wompi_reference' => 'FACTURA-6761067-ORIGINAL',
        'wompi_transaction_id' => 'TX-ORIGINAL',
        'wompi_status' => 'APPROVED',
        'wompi_amount_in_cents' => 9900000,
        'paid_at' => $paidAt,
    ]);
    $original = $existing->fresh()->getAttributes();

    $preview = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv(),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    expect($preview->viewData('summary')['paid'])->toBe(1)
        ->and($preview->viewData('rows')[0]['status'])->toBe(InvoiceSimpleImportService::STATUS_PAID);

    $this->post(route('invoices.simple-import.confirm'), [
        'token' => $preview->viewData('token'),
    ])->assertOk();

    expect($existing->fresh()->getAttributes())->toBe($original)
        ->and(SimpleInvoice::count())->toBe(1);
});

test('Wompi webhook updates the simplified invoice selected by its exact payment reference', function () {
    $invoice = SimpleInvoice::create([
        'numero' => '7911062',
        'codigo' => '7911062',
        'refpago' => '7911062',
        'valfactura' => 2000,
        'fecha' => now()->addDays(3),
        'nombre' => 'Plaza de ferias',
        'status' => 'pendiente',
        'wompi_reference' => '7911062',
    ]);

    $payload = [
        'event' => 'transaction.updated',
        'data' => [
            'transaction' => [
                'id' => 'TX-SIMPLE-1',
                'status' => 'APPROVED',
                'amount_in_cents' => 200000,
                'reference' => '7911062',
                'finalized_at' => now()->toIso8601String(),
            ],
        ],
    ];

    $this->postJson('/webhook/wompi', $payload)->assertOk();

    expect($invoice->fresh()->status)->toBe('pagada')
        ->and($invoice->fresh()->wompi_status)->toBe('APPROVED')
        ->and($invoice->fresh()->wompi_transaction_id)->toBe('TX-SIMPLE-1')
        ->and($invoice->fresh()->paid_at)->not->toBeNull();
});

test('duplicates and invalid rows do not stop valid rows', function () {
    $rows = implode("\n", [
        '100;Primera;7001;40.150',
        '200;Duplicada;7001;80.000',
        '300;;7003;-50',
        '400;Cuarta;7004;1.250.000',
    ]);
    $preview = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv('REFERNCIA', $rows),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    expect($preview->viewData('summary'))
        ->new->toBe(2)
        ->duplicates->toBe(1)
        ->invalid->toBe(1)
        ->total_value->toBe(1290150);

    $this->post(route('invoices.simple-import.confirm'), [
        'token' => $preview->viewData('token'),
    ])->assertOk();

    expect(SimpleInvoice::count())->toBe(2)
        ->and(SimpleInvoice::where('refpago', '7001')->value('valfactura'))->toBe(40150)
        ->and(SimpleInvoice::where('refpago', '7004')->value('valfactura'))->toBe(1250000);
});

test('confirmation rechecks references and cannot reuse a token', function () {
    $user = simpleImportUser();
    $preview = $this->actingAs($user)
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv(),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);
    $token = $preview->viewData('token');

    SimpleInvoice::create([
        'numero' => 'RACE',
        'codigo' => 'RACE',
        'refpago' => '6761067',
        'valfactura' => 100,
        'fecha' => now()->addDay(),
        'nombre' => 'Creada durante la confirmacion',
        'status' => 'pendiente',
    ]);

    $first = $this->post(route('invoices.simple-import.confirm'), ['token' => $token]);
    $first->assertOk();

    expect($first->viewData('result')['duplicates'])->toBe(1)
        ->and(SimpleInvoice::count())->toBe(1);

    $this->post(route('invoices.simple-import.confirm'), ['token' => $token])
        ->assertRedirect(route('invoices.simple-import.form'));
});

test('past payment dates are rejected', function () {
    $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv(),
            'fecha_limite' => now()->subDay()->toDateString(),
        ])
        ->assertSessionHasErrors('fecha_limite');

    expect(SimpleInvoice::count())->toBe(0);
});

test('formula values are not evaluated or imported', function () {
    $response = $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => simpleImportCsv('REFERNCIA', '100;Formula;7001;=200+300'),
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ]);

    $response->assertOk();

    expect($response->viewData('summary')['invalid'])->toBe(1)
        ->and($response->viewData('summary')['new'])->toBe(0)
        ->and(SimpleInvoice::count())->toBe(0);
});

test('ambiguous equivalent headers are rejected', function () {
    $file = UploadedFile::fake()->createWithContent(
        'facturas.csv',
        "CODIGO;NOMBRE;REFERNCIA;REFERENCIA;VALOR\n100;Nombre;7001;7002;50000\n"
    );

    $this->actingAs(simpleImportUser())
        ->post(route('invoices.simple-import.preview'), [
            'file' => $file,
            'fecha_limite' => now()->addDays(5)->toDateString(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(SimpleInvoice::count())->toBe(0);
});

test('normalizes identifiers and Colombian money formats', function (mixed $input, string $expected) {
    $service = new InvoiceSimpleImportService;
    $method = new ReflectionMethod($service, 'normalizeIdentifier');

    expect($method->invoke($service, $input))->toBe($expected);
})->with([
    ['7811036.0', '7811036'],
    ['6.761.067', '6761067'],
    [' 6761067 ', '6761067'],
    ['00123', '00123'],
    ['6.761067E+6', '6761067'],
]);

test('normalizes values as integer Colombian pesos', function (mixed $input, int $expected) {
    $service = new InvoiceSimpleImportService;
    $method = new ReflectionMethod($service, 'normalizeMoney');

    expect($method->invoke($service, $input))->toBe($expected);
})->with([
    ['$ 40.150', 40150],
    ['40,150', 40150],
    ['40150.00', 40150],
    ['1.250.000', 1250000],
]);

test('legacy importer remains registered', function () {
    expect(Route::has('invoices.import.form'))->toBeTrue()
        ->and(Route::has('invoices.import'))->toBeTrue();
});
