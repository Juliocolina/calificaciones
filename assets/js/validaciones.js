/**
 * Sistema de Validaciones Frontend - Misión Sucre
 * Validaciones con regex y SweetAlert2
 */

// Patrones de validación (regex)
const PATRONES = {
    // Cédula venezolana: 7-8 dígitos
    cedula: /^[0-9]{7,8}$/,
    
    // Email válido
    email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    
    // Teléfono venezolano: 0412-1234567 o 04121234567
    telefono: /^(0(412|414|424|416|426|212|241|243|244|245|246|247|248|249|251|252|253|254|255|256|257|258|259|261|262|263|264|265|266|267|268|269|271|272|273|274|275|276|277|278|279|281|282|283|284|285|286|287|288|289|291|292|293|294|295))[0-9]{7}$/,
    
    // Contraseña: min 16 caracteres, 1 mayúscula, 1 minúscula, 1 número, 1 carácter especial
    password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{16,}$/,
    
    // Solo letras y espacios (nombres)
    soloLetras: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
    
    // Alfanumérico con espacios y guiones (trimestres)
    alfanumerico: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\-]+$/,
    
    // Solo números
    soloNumeros: /^[0-9]+$/,
    
    // Fecha válida (YYYY-MM-DD)
    fecha: /^\d{4}-\d{2}-\d{2}$/
};

// Mensajes de error
const MENSAJES = {
    cedula: 'La cédula debe tener entre 7 y 8 dígitos',
    email: 'Ingrese un correo electrónico válido',
    telefono: 'Ingrese un teléfono venezolano válido (Ej: 04121234567)',
    password: 'La contraseña debe tener mínimo 16 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial (@$!%*?&)',
    soloLetras: 'Solo se permiten letras y espacios',
    alfanumerico: 'Solo se permiten letras, números, espacios y guiones',
    soloNumeros: 'Solo se permiten números',
    fecha: 'Ingrese una fecha válida',
    requerido: 'Este campo es obligatorio',
    minLength: 'Debe tener al menos {min} caracteres',
    maxLength: 'No puede exceder {max} caracteres'
};

/**
 * Validar un campo específico
 */
function validarCampo(campo, tipo, opciones = {}) {
    const valor = campo.value.trim();
    const nombre = campo.getAttribute('data-nombre') || campo.name || 'Campo';
    
    // Validar campo requerido
    if (opciones.requerido && valor === '') {
        mostrarError(campo, MENSAJES.requerido);
        return false;
    }
    
    // Si está vacío y no es requerido, es válido
    if (valor === '' && !opciones.requerido) {
        limpiarError(campo);
        return true;
    }
    
    // Validar longitud mínima
    if (opciones.minLength && valor.length < opciones.minLength) {
        mostrarError(campo, MENSAJES.minLength.replace('{min}', opciones.minLength));
        return false;
    }
    
    // Validar longitud máxima
    if (opciones.maxLength && valor.length > opciones.maxLength) {
        mostrarError(campo, MENSAJES.maxLength.replace('{max}', opciones.maxLength));
        return false;
    }
    
    // Validar patrón específico
    if (PATRONES[tipo] && !PATRONES[tipo].test(valor)) {
        mostrarError(campo, MENSAJES[tipo]);
        return false;
    }
    
    // Si llegó aquí, es válido
    limpiarError(campo);
    return true;
}

/**
 * Validar confirmación de contraseña
 */
function validarConfirmacionPassword(campoPassword, campoConfirmar) {
    if (campoPassword.value !== campoConfirmar.value) {
        mostrarError(campoConfirmar, 'Las contraseñas no coinciden');
        return false;
    }
    limpiarError(campoConfirmar);
    return true;
}

/**
 * Mostrar error en el campo
 */
function mostrarError(campo, mensaje) {
    // Remover clases de éxito
    campo.classList.remove('is-valid');
    // Agregar clase de error
    campo.classList.add('is-invalid');
    
    // Buscar o crear div de error
    let errorDiv = campo.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        campo.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = mensaje;
}

/**
 * Limpiar error del campo
 */
function limpiarError(campo) {
    campo.classList.remove('is-invalid');
    campo.classList.add('is-valid');
    
    const errorDiv = campo.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Validar formulario completo
 */
function validarFormulario(formulario) {
    let esValido = true;
    const errores = [];
    
    // Obtener todas las validaciones del formulario
    const campos = formulario.querySelectorAll('[data-validar]');
    
    campos.forEach(campo => {
        const validaciones = JSON.parse(campo.getAttribute('data-validar'));
        const tipo = validaciones.tipo;
        const opciones = validaciones.opciones || {};
        
        if (!validarCampo(campo, tipo, opciones)) {
            esValido = false;
            errores.push(campo.getAttribute('data-nombre') || campo.name);
        }
    });
    
    // Validar confirmación de contraseña si existe
    const passwordField = formulario.querySelector('input[name="clave"]');
    const confirmField = formulario.querySelector('input[name="confirmar_clave"]');
    
    if (passwordField && confirmField) {
        if (!validarConfirmacionPassword(passwordField, confirmField)) {
            esValido = false;
            errores.push('Confirmación de contraseña');
        }
    }
    
    return { esValido, errores };
}

/**
 * Mostrar alerta de errores con SweetAlert2
 */
function mostrarAlertaErrores(errores) {
    const listaErrores = errores.map(error => `• ${error}`).join('<br>');
    
    Swal.fire({
        icon: 'error',
        title: 'Errores en el formulario',
        html: `Por favor corrija los siguientes errores:<br><br>${listaErrores}`,
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Mostrar alerta de éxito
 */
function mostrarAlertaExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: mensaje,
        confirmButtonColor: '#28a745'
    });
}

/**
 * Inicializar validaciones en tiempo real
 */
function inicializarValidaciones() {
    document.addEventListener('DOMContentLoaded', function() {
        // Validación en tiempo real para todos los campos con data-validar
        const campos = document.querySelectorAll('[data-validar]');
        
        campos.forEach(campo => {
            // Validar al perder el foco
            campo.addEventListener('blur', function() {
                const validaciones = JSON.parse(this.getAttribute('data-validar'));
                validarCampo(this, validaciones.tipo, validaciones.opciones || {});
            });
            
            // Limpiar error al empezar a escribir
            campo.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                    if (errorDiv) errorDiv.remove();
                }
            });
        });
        
        // Validar formularios al enviar
        const formularios = document.querySelectorAll('form[data-validar-form]');
        
        formularios.forEach(formulario => {
            formulario.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const resultado = validarFormulario(this);
                
                if (resultado.esValido) {
                    // Si es válido, enviar el formulario
                    this.submit();
                } else {
                    // Mostrar errores
                    mostrarAlertaErrores(resultado.errores);
                }
            });
        });
    });
}

// Funciones de utilidad para validaciones específicas
const ValidacionesUtils = {
    // Validar edad mínima
    validarEdadMinima: function(fechaNacimiento, edadMinima = 16) {
        const hoy = new Date();
        const nacimiento = new Date(fechaNacimiento);
        const edad = hoy.getFullYear() - nacimiento.getFullYear();
        const mesActual = hoy.getMonth();
        const mesNacimiento = nacimiento.getMonth();
        
        if (mesActual < mesNacimiento || (mesActual === mesNacimiento && hoy.getDate() < nacimiento.getDate())) {
            edad--;
        }
        
        return edad >= edadMinima;
    },
    
    // Formatear teléfono venezolano
    formatearTelefono: function(telefono) {
        const limpio = telefono.replace(/\D/g, '');
        if (limpio.length === 11) {
            return limpio.replace(/(\d{4})(\d{3})(\d{4})/, '$1-$2-$3');
        }
        return telefono;
    },
    
    // Generar contraseña segura
    generarPasswordSegura: function() {
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789@$!%*?&';
        let password = '';
        
        // Asegurar al menos uno de cada tipo
        password += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'[Math.floor(Math.random() * 26)]; // Mayúscula
        password += 'abcdefghijklmnopqrstuvwxyz'[Math.floor(Math.random() * 26)]; // Minúscula
        password += '0123456789'[Math.floor(Math.random() * 10)]; // Número
        password += '@$!%*?&'[Math.floor(Math.random() * 7)]; // Especial
        
        // Completar hasta 16 caracteres
        for (let i = 4; i < 16; i++) {
            password += caracteres[Math.floor(Math.random() * caracteres.length)];
        }
        
        // Mezclar caracteres
        return password.split('').sort(() => Math.random() - 0.5).join('');
    }
};

/**
 * Confirmar eliminación con SweetAlert2
 */
function confirmarEliminacion(elemento, mensaje = '¿Estás seguro de eliminar este elemento?', titulo = 'Confirmar eliminación') {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Si es un formulario, enviarlo
            if (elemento.tagName === 'FORM') {
                elemento.submit();
            }
            // Si es un enlace, redirigir
            else if (elemento.tagName === 'A') {
                window.location.href = elemento.href;
            }
            // Si es un botón con data-action
            else if (elemento.dataset.action) {
                eval(elemento.dataset.action);
            }
        }
    });
}

/**
 * Confirmar acción genérica
 */
function confirmarAccion(titulo, mensaje, callback, tipo = 'warning') {
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: tipo,
        showCancelButton: true,
        confirmButtonColor: tipo === 'warning' ? '#ffc107' : '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

/**
 * Mostrar alerta de carga/procesando
 */
function mostrarCargando(mensaje = 'Procesando...') {
    Swal.fire({
        title: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Cerrar alerta de carga
 */
function cerrarCargando() {
    Swal.close();
}

/**
 * Validar selección en tablas
 */
function validarSeleccion(checkboxes, mensaje = 'Debe seleccionar al menos un elemento') {
    const seleccionados = Array.from(checkboxes).filter(cb => cb.checked);
    
    if (seleccionados.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Selección requerida',
            text: mensaje,
            confirmButtonColor: '#ffc107'
        });
        return false;
    }
    
    return seleccionados;
}

/**
 * Inicializar eventos automáticos
 */
function inicializarEventosAutomaticos() {
    document.addEventListener('DOMContentLoaded', function() {
        // Botones de eliminación automáticos
        document.querySelectorAll('[data-eliminar]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const mensaje = this.dataset.mensaje || '¿Estás seguro de eliminar este elemento?';
                const titulo = this.dataset.titulo || 'Confirmar eliminación';
                
                confirmarEliminacion(this.closest('form') || this, mensaje, titulo);
            });
        });
        
        // Formularios con confirmación
        document.querySelectorAll('form[data-confirmar]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const mensaje = this.dataset.mensaje || '¿Desea continuar con esta acción?';
                const titulo = this.dataset.titulo || 'Confirmar acción';
                
                confirmarAccion(titulo, mensaje, () => {
                    this.submit();
                });
            });
        });
        
        // Enlaces con confirmación
        document.querySelectorAll('a[data-confirmar]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const mensaje = this.dataset.mensaje || '¿Desea continuar?';
                const titulo = this.dataset.titulo || 'Confirmar';
                
                confirmarAccion(titulo, mensaje, () => {
                    window.location.href = this.href;
                });
            });
        });
    });
}

// Inicializar automáticamente
inicializarValidaciones();
inicializarEventosAutomaticos();