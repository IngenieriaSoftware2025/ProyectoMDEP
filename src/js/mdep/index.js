import { Modal } from "bootstrap";
import Swal from "sweetalert2";
import { validarFormulario } from '../funciones';
import DataTable from "datatables.net-bs5";
import { lenguaje } from "../lenguaje";

const FormDependencias = document.getElementById('FormDependencias');
const BtnGuardar = document.getElementById('BtnGuardar');
const BtnModificar = document.getElementById('BtnModificar');
const BtnNuevaDependencia = document.getElementById('BtnNuevaDependencia');
const BtnVerUbicaciones = document.getElementById('BtnVerUbicaciones');
const modalElement = document.getElementById('modalDependencia');
const modalDependencia = modalElement ? new Modal(modalElement) : null;

const validarCamposObligatorios = () => {
    const camposObligatorios = [
        { id: 'dep_desc_lg', nombre: 'Descripción Larga', minimo: 10, maximo: 100 },
        { id: 'dep_desc_md', nombre: 'Descripción Mediana', minimo: 5, maximo: 35 },
        { id: 'dep_desc_ct', nombre: 'Descripción Corta', minimo: 3, maximo: 15 },
        { id: 'dep_clase', nombre: 'Clase', minimo: 1, maximo: 1 }
    ];

    for (let campo of camposObligatorios) {
        const input = document.getElementById(campo.id);
        const valor = input.value.trim();

        if (!valor || valor.length < campo.minimo) {
            Swal.fire({
                icon: "error",
                title: "Campo Obligatorio Incompleto",
                text: `${campo.nombre} debe tener al menos ${campo.minimo} caracteres`,
                showConfirmButton: true,
            });
            input.focus();
            return false;
        }

        if (valor.length > campo.maximo) {
            Swal.fire({
                icon: "error", 
                title: "Campo Excede Límite",
                text: `${campo.nombre} no puede exceder ${campo.maximo} caracteres`,
                showConfirmButton: true,
            });
            input.focus();
            return false;
        }
    }
    return true;
};


const abrirModalNueva = () => {
    document.getElementById('modalDependenciaLabel').textContent = 'Nueva Dependencia';
    FormDependencias.reset();
    
    const depLlaveInput = document.getElementById('dep_llave');
    if (depLlaveInput) {
        depLlaveInput.value = '';
        depLlaveInput.name = ''; 
        console.log('🔧 Input dep_llave deshabilitado para nueva dependencia');
    }
    
    BtnGuardar.classList.remove('d-none');
    BtnModificar.classList.add('d-none');
    modalDependencia.show();
}

const abrirModalEditar = (datos) => {
    document.getElementById('modalDependenciaLabel').textContent = 'Modificar Dependencia';
    
    let depLlaveInput = document.getElementById('dep_llave');
    if (!depLlaveInput) {
        depLlaveInput = document.createElement('input');
        depLlaveInput.type = 'hidden';
        depLlaveInput.id = 'dep_llave';
        FormDependencias.appendChild(depLlaveInput);
    }
    
    depLlaveInput.name = 'dep_llave';
    depLlaveInput.value = datos.id;
    
    document.getElementById('dep_desc_lg').value = datos.descLg;
    document.getElementById('dep_desc_md').value = datos.descMd;
    document.getElementById('dep_desc_ct').value = datos.descCt;
    document.getElementById('dep_clase').value = datos.clase;
    document.getElementById('dep_latitud').value = datos.latitud || '';
    document.getElementById('dep_longitud').value = datos.longitud || '';
    
    console.log('Modificar dependencia ID:', datos.id);
    
    BtnGuardar.classList.add('d-none');
    BtnModificar.classList.remove('d-none');
    modalDependencia.show();
}

const GuardarDependencia = async (event) => {
    event.preventDefault();
    BtnGuardar.disabled = true;

    if (!validarCamposObligatorios()) {
        BtnGuardar.disabled = false;
        return;
    }

    const body = new FormData(FormDependencias);
    body.delete('dep_llave');
    
    const url = '/ProyectoMDEP/mdep/guardarAPI';

    try {
        console.log('=== ENVIANDO REQUEST ===');
        
        const respuesta = await fetch(url, { 
            method: 'POST', 
            body 
        });
        
        console.log('Response status:', respuesta.status);
        
        const datos = await respuesta.json();
        console.log('=== RESPUESTA SERVIDOR ===');
        console.log(datos);

        if (datos.codigo == 1) {
            await Swal.fire({
                icon: "success",
                title: "¡Dependencia Creada!",
                text: datos.mensaje,
                showConfirmButton: true,
            });
            modalDependencia.hide();
            BuscarDependencias();
        } else {
            console.error('Error del servidor:', datos);
            await Swal.fire({
                icon: "error",
                title: "Error al Crear",
                text: datos.mensaje,
                showConfirmButton: true,
            });
        }
    } catch (error) {
        console.error('Error fetch:', error);
        await Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo conectar con el servidor.",
            showConfirmButton: true,
        });
    }
    
    BtnGuardar.disabled = false;
}

const ModificarDependencia = async (event) => {
    event.preventDefault();
    BtnModificar.disabled = true;

    if (!validarCamposObligatorios()) {
        BtnModificar.disabled = false;
        return;
    }

    const body = new FormData(FormDependencias);
    
    // AGREGAR ESTA VERIFICACIÓN
    console.log('=== DATOS ENVIANDO MODIFICAR ===');
    for (let [key, value] of body.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // FORZAR dep_llave si no existe
    const depLlaveInput = document.getElementById('dep_llave');
    if (depLlaveInput && depLlaveInput.value) {
        body.set('dep_llave', depLlaveInput.value);
        console.log('dep_llave forzado:', depLlaveInput.value);
    }
    
    const url = '/ProyectoMDEP/mdep/modificarAPI';

    try {
        const respuesta = await fetch(url, { method: 'POST', body });
        const datos = await respuesta.json();

        if (datos.codigo == 1) {
            await Swal.fire({
                icon: "success",
                title: "¡Dependencia Modificada!",
                text: datos.mensaje,
                showConfirmButton: true,
            });
            modalDependencia.hide();
            BuscarDependencias();
        } else {
            await Swal.fire({
                icon: "error", 
                title: "Error al Modificar",
                text: datos.mensaje,
                showConfirmButton: true,
            });
        }
    } catch (error) {
        console.error('Error:', error);
        await Swal.fire({
            icon: "error",
            title: "Error de conexión", 
            text: "No se pudo conectar con el servidor.",
            showConfirmButton: true,
        });
    }
    BtnModificar.disabled = false;
}

const BuscarDependencias = async () => {
    try {
        const respuesta = await fetch('/ProyectoMDEP/mdep/buscarAPI');
        const datos = await respuesta.json();

        if (datos.codigo == 1) {
            datatable.clear().draw();
            if (datos.data && datos.data.length > 0) {
                datatable.rows.add(datos.data).draw();
            }
        } else {
            console.error('Error al obtener dependencias:', datos.mensaje);
        }
    } catch (error) {
        console.error('Error al cargar dependencias:', error);
    }
}

const DeshabilitarDependencia = async (e) => {
    const id = e.currentTarget.dataset.id;

    const { value: justificacion } = await Swal.fire({
        title: 'Deshabilitar Dependencia',
        text: 'Escriba la justificación para deshabilitar:',
        input: 'textarea',
        inputPlaceholder: 'Justificación detallada (mínimo 10 caracteres)...',
        showCancelButton: true,
        confirmButtonText: 'Deshabilitar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        inputValidator: (value) => {
            if (!value || value.length < 10) {
                return 'La justificación debe tener al menos 10 caracteres'
            }
        }
    });

    if (justificacion) {
        const body = new FormData();
        body.append('dep_llave', id);
        body.append('justificacion', justificacion);

        try {
            const respuesta = await fetch('/ProyectoMDEP/mdep/deshabilitarAPI', { method: 'POST', body });
            const datos = await respuesta.json();

            if (datos.codigo == 1) {
                await Swal.fire({
                    icon: "success",
                    title: "¡Dependencia Deshabilitada!",
                    text: datos.mensaje,
                    showConfirmButton: true,
                });
                BuscarDependencias();
            } else {
                throw new Error(datos.mensaje);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo deshabilitar la dependencia: ' + error.message
            });
        }
    }
}

const HabilitarDependencia = async (e) => {
    const id = e.currentTarget.dataset.id;

    const { value: justificacion } = await Swal.fire({
        title: 'Habilitar Dependencia',
        text: 'Escriba la justificación para habilitar:',
        input: 'textarea',
        inputPlaceholder: 'Justificación detallada (mínimo 10 caracteres)...',
        showCancelButton: true,
        confirmButtonText: 'Habilitar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#28a745',
        inputValidator: (value) => {
            if (!value || value.length < 10) {
                return 'La justificación debe tener al menos 10 caracteres'
            }
        }
    });

    if (justificacion) {
        const body = new FormData();
        body.append('dep_llave', id);
        body.append('justificacion', justificacion);

        try {
            const respuesta = await fetch('/ProyectoMDEP/mdep/habilitarAPI', { method: 'POST', body });
            const datos = await respuesta.json();

            if (datos.codigo == 1) {
                await Swal.fire({
                    icon: "success",
                    title: "¡Dependencia Habilitada!",
                    text: datos.mensaje,
                    showConfirmButton: true,
                });
                BuscarDependencias();
            } else {
                throw new Error(datos.mensaje);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo habilitar la dependencia: ' + error.message
            });
        }
    }
}

const MostrarDetalles = async (e) => {
    const id = e.currentTarget.dataset.id;
    
    try {
        const respuesta = await fetch(`/ProyectoMDEP/mdep/obtenerPDFAPI?id=${id}`);
        
        if (respuesta.ok) {
            const blob = await respuesta.blob();
            const url = window.URL.createObjectURL(blob);
            window.open(url, '_blank');
            window.URL.revokeObjectURL(url);
        } else {
            const datos = await respuesta.json();
            Swal.fire({
                icon: 'info',
                title: 'Sin Justificación',
                text: datos.mensaje || 'Esta dependencia no tiene PDF de justificación generado.'
            });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo obtener el documento de justificación.'
        });
    }
}

const VerUbicaciones = () => {
    window.location.href = '/ProyectoMDEP/mdep/ubicaciones';
}

const obtenerTextoClase = (clase) => {
    const clases = {
        'A': 'A - Administrativo',
        'O': 'O - Operativo', 
        'D': 'D - Docencia',
        'R': 'R - Rescate'
    };
    return clases[clase] || clase;
}

const datatable = new DataTable('#TableDependencias', {
    dom: 'lfrtip',
    language: lenguaje,
    data: [],
    order: [[0, 'desc']],
    columns: [
        {
            title: 'No.',
            data: 'dep_llave',
            width: '5%',
            render: (data, type, row, meta) => meta.row + 1
        },
        { 
            title: 'Descripción Larga / Corta', 
            data: 'dep_desc_lg',
            render: (data, type, row) => {
                return `<div><strong>${data}</strong><br><small class="text-muted">${row.dep_desc_ct}</small></div>`;
            }
        },
        { 
            title: 'Clase', 
            data: 'dep_clase',
            render: (data) => obtenerTextoClase(data)
        },
        { 
            title: 'Longitud', 
            data: 'dep_longitud',
            render: (data) => data || '<span class="text-muted">No asignada</span>'
        },
        { 
            title: 'Latitud', 
            data: 'dep_latitud',
            render: (data) => data || '<span class="text-muted">No asignada</span>'
        },
        {
            title: 'Logo',
            data: 'dep_ruta_logo',
            render: (data) => {
                return data ? '<span class="badge bg-success">Sí</span>' : '<span class="text-muted">No asignado</span>';
            }
        },
        {
            title: 'Estado',
            data: 'dep_situacion',
            render: (data) => {
                return data == 1 ? 
                    '<span class="badge bg-success">ACTIVO</span>' : 
                    '<span class="badge bg-danger">INACTIVO</span>';
            }
        },
        {
            title: 'Acciones',
            data: 'dep_llave',
            searchable: false,
            orderable: false,
            render: (data, type, row) => {
                const btnEstado = row.dep_situacion == 1 ? 
                    `<button class='btn btn-danger btn-sm deshabilitar mx-1' data-id="${data}">
                        Deshabilitar
                    </button>` : 
                    `<button class='btn btn-success btn-sm habilitar mx-1' data-id="${data}">
                        Habilitar
                    </button>`;
                
                return `
                 <div class='d-flex justify-content-center'>
                     <button class='btn btn-warning btn-sm modificar mx-1' 
                         data-id="${data}" 
                         data-desc-lg="${row.dep_desc_lg}"  
                         data-desc-md="${row.dep_desc_md}"  
                         data-desc-ct="${row.dep_desc_ct}"
                         data-clase="${row.dep_clase}"
                         data-latitud="${row.dep_latitud || ''}"
                         data-longitud="${row.dep_longitud || ''}">
                        Modificar
                     </button>
                     ${btnEstado}
                     <button class='btn btn-primary btn-sm detalles mx-1' data-id="${data}">
                        <i class="bi bi-file-earmark-pdf"></i>
                     </button>
                 </div>`;
            }
        }
    ]
});

if (BtnNuevaDependencia) {
    BtnNuevaDependencia.addEventListener('click', abrirModalNueva);
}

if (BtnGuardar) {
    BtnGuardar.addEventListener('click', GuardarDependencia);
}

if (BtnModificar) {
    BtnModificar.addEventListener('click', ModificarDependencia);
}

if (BtnVerUbicaciones) {
    BtnVerUbicaciones.addEventListener('click', VerUbicaciones);
}

datatable.on('click', '.deshabilitar', DeshabilitarDependencia);
datatable.on('click', '.habilitar', HabilitarDependencia);
datatable.on('click', '.modificar', (e) => {
    const datos = e.currentTarget.dataset;
    abrirModalEditar(datos);
});
datatable.on('click', '.detalles', MostrarDetalles);

BuscarDependencias();