<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

/**
 * Autenticación, autorización por rol, cabeceras de seguridad y auditoría.
 */
class SeguridadTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('dispensaciones.buscar'))->assertRedirect(route('login'));
    }

    public function test_login_correcto_regenera_la_sesion(): void
    {
        $u = $this->usuario('tecnico');

        $this->post('/login', ['email' => $u->email, 'password' => 'Clave#Segura2026'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $u = User::factory()->inactivo()->create()->assignRole('tecnico');

        $this->post('/login', ['email' => $u->email, 'password' => 'Clave#Segura2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $u = $this->usuario('tecnico');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $u->email, 'password' => 'incorrecta']);
        }

        // Incluso con la contraseña correcta, el sexto intento queda bloqueado.
        $this->post('/login', ['email' => $u->email, 'password' => 'Clave#Segura2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_matriz_de_permisos_por_rol(): void
    {
        $casos = [
            // ruta => [admin, tecnico, quimico]
            'usuarios.index' => [200, 403, 403],
            'dispensaciones.buscar' => [403, 200, 200],
            'remesas.create' => [403, 200, 200],
            'controlados.index' => [200, 403, 200],
            'auditoria.index' => [200, 403, 403],
        ];

        $usuarios = ['administrador' => $this->usuario('administrador'), 'tecnico' => $this->usuario('tecnico'), 'quimico' => $this->usuario('quimico')];

        foreach ($casos as $ruta => $esperados) {
            foreach (array_values($usuarios) as $i => $usuario) {
                $this->actingAs($usuario)->get(route($ruta))->assertStatus($esperados[$i]);
            }
        }
    }

    public function test_usuario_desactivado_pierde_la_sesion_abierta(): void
    {
        $u = $this->usuario('tecnico');
        $this->actingAs($u)->get(route('dashboard'))->assertOk();

        $u->update(['activo' => false]);

        $this->actingAs($u->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_contrasena_debil_es_rechazada(): void
    {
        $this->actingAs($this->usuario('administrador'))->post(route('usuarios.store'), [
            'rut' => '11.111.111-1', 'name' => 'Nuevo', 'email' => 'nuevo@farmacia.test', 'rol' => 'tecnico',
            'password' => 'clave123', 'password_confirmation' => 'clave123',
        ])->assertSessionHasErrors('password');
    }

    public function test_auditoria_registra_cambios_sin_datos_personales(): void
    {
        $this->usuario('tecnico');
        $eventos = AuditoriaService::eventos();

        $creacion = collect($eventos)->firstWhere('modelo', 'User');
        $this->assertNotNull($creacion);
        $this->assertSame('[protegido]', $creacion['despues']['password']);
    }
}
