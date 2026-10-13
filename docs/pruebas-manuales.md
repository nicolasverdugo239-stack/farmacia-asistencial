# Pruebas manuales (checklist)

Complementan las pruebas automáticas (PHPUnit y k6). Se ejecutan con los usuarios de demostración
(contraseña `Farmacia#Demo2026`). Marca cada caso y anota la evidencia (captura) en el informe.

| ID | Caso | Rol | Pasos | Resultado esperado | OK |
|----|------|-----|-------|--------------------|----|
| PM-01 | Menú según rol | Los 3 | Iniciar sesión con cada usuario | Admin no ve Dispensar; Técnico no ve Libro ni Usuarios; QF ve todo menos Usuarios/Auditoría | ☐ |
| PM-02 | Acceso directo por URL | Técnico | Escribir `/controlados` en la barra de direcciones | Error 403 (no basta con ocultar el botón) | ☐ |
| PM-03 | Bloqueo por fuerza bruta | — | 5 contraseñas incorrectas seguidas | Mensaje de bloqueo por 15 minutos | ☐ |
| PM-04 | Dispensación FEFO | Técnico | Buscar folio `10452` | Se preselecciona el lote L-2311 (vence primero) | ☐ |
| PM-05 | Retiro por tercero | Técnico | Marcar "Retira un tercero" y confirmar sin datos | Pide nombre, RUT y parentesco; no descuenta stock | ☐ |
| PM-06 | RUT inválido | Técnico | Tercero con RUT `12.345.678-9` | "no es un RUT válido" | ☐ |
| PM-07 | Receta ya dispensada | Técnico | Dispensar `10452` dos veces | La segunda vez no aparece como vigente | ☐ |
| PM-08 | Controlado por técnico | Técnico | Abrir receta `10453` (clonazepam) y confirmar | 403: debe dispensarla el QF | ☐ |
| PM-09 | Firma de egreso | QF | Registrar egreso con contraseña incorrecta | No se registra; mensaje de contraseña | ☐ |
| PM-10 | Hash encadenado | QF | Registrar un egreso y pulsar "Verificar integridad" | "Cadena íntegra" | ☐ |
| PM-11 | Alteración del libro | QF | En MySQL: `UPDATE movimientos_controlados SET cantidad=1 WHERE id=1;` y verificar | "Cadena rota en el registro #1" | ☐ |
| PM-12 | Conteo físico | QF | Conteo de Clonazepam = 58 | Alerta de descuadre en Inicio | ☐ |
| PM-13 | Carro por sección | Técnico | Registrar consumo en Atropina (Paro) | Baja solo el lado Paro; alerta "por reponer" | ☐ |
| PM-14 | Remesa con lote vencido | Técnico | Fecha de vencimiento de ayer | Rechazado: no se recibe un lote vencido | ☐ |
| PM-15 | Auditoría | Admin | Abrir Auditoría tras las pruebas | Eventos con usuario, IP y cambios; RUT como `[protegido]` | ☐ |
| PM-16 | Responsive | Cualquiera | Abrir en el celular o con DevTools a 390 px | Menú desplegable, una columna, botones ≥ 44 px | ☐ |
| PM-17 | Usuario desactivado | Admin | Desactivar al técnico con su sesión abierta | En su siguiente clic vuelve al login | ☐ |
