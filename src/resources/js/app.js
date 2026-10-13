import './bootstrap';

// Remesas: agrega filas de lote a partir de la plantilla <template id="linea-tpl">.
// Va en este archivo (y no en un <script> dentro de la vista) para cumplir la CSP "script-src 'self'".
document.addEventListener('DOMContentLoaded', () => {
    const boton = document.getElementById('agregar-linea');
    const plantilla = document.getElementById('linea-tpl');
    const contenedor = document.getElementById('lineas');
    if (!boton || !plantilla || !contenedor) return;

    boton.addEventListener('click', () => {
        const indice = contenedor.querySelectorAll('[data-linea]').length;
        const html = plantilla.innerHTML.replaceAll('__i__', String(indice));
        contenedor.insertAdjacentHTML('beforeend', html);
    });

    contenedor.addEventListener('click', (e) => {
        const quitar = e.target.closest('[data-quitar]');
        if (quitar && contenedor.querySelectorAll('[data-linea]').length > 1) {
            quitar.closest('[data-linea]').remove();
        }
    });
});
