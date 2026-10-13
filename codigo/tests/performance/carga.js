/**
 * Prueba de rendimiento con k6 (RNF05: buen tiempo de respuesta en consultas de stock).
 *
 * Uso:   k6 run tests/performance/carga.js
 *        k6 run -e BASE_URL=http://localhost:8000 tests/performance/carga.js
 *
 * Simula hasta 20 usuarios (técnicos) buscando recetas y abriendo la dispensación.
 * Criterio de aceptación: 95% de las respuestas en menos de 500 ms y menos de 1% de errores.
 */
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = __ENV.BASE_URL || 'http://localhost:8000';
const EMAIL = __ENV.EMAIL || 'tecnico@farmacia.test';
const PASSWORD = __ENV.PASSWORD || 'Farmacia#Demo2026';

export const options = {
    stages: [
        { duration: '30s', target: 10 },
        { duration: '1m', target: 20 },
        { duration: '30s', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],
        http_req_failed: ['rate<0.01'],
    },
};

export function setup() {
    // Nada que preparar: cada usuario virtual inicia su propia sesión.
}

function iniciarSesion() {
    const pagina = http.get(`${BASE}/login`);
    const token = pagina.html().find('input[name=_token]').attr('value');
    const res = http.post(`${BASE}/login`, { _token: token, email: EMAIL, password: PASSWORD });
    check(res, { 'login correcto': (r) => r.status === 200 && !r.url.includes('/login') });
}

export default function () {
    if (__ITER === 0) {
        iniciarSesion();
    }

    const inicio = http.get(`${BASE}/`);
    check(inicio, { 'inicio 200': (r) => r.status === 200 });

    const busqueda = http.get(`${BASE}/dispensaciones?q=no-existe`);
    check(busqueda, { 'búsqueda 200': (r) => r.status === 200 });

    const carro = http.get(`${BASE}/carros`);
    check(carro, { 'carro 200': (r) => r.status === 200 });

    sleep(1);
}
