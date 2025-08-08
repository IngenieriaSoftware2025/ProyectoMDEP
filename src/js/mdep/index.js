import { Modal } from "bootstrap";
import Swal from "sweetalert2";
import { validarFormulario } from '../funciones';
import DataTable from "datatables.net-bs5";
import { lenguaje } from "../lenguaje";

let FormDependencias, BtnGuardar, BtnModificar, BtnNuevaDependencia, modalDependencia;
let datatable;

document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initializeDataTable();
    setupEventListeners();
    BuscarDependencias();
});

const initializeElements = () => {
    FormDependencias = document.getElementById('FormDependencias');
    BtnGuardar = document.getElementById('BtnGuardar');
    BtnModificar = document.getElementById('BtnModificar');
    BtnNuevaDependencia = document.getElementById('BtnNuevaDependencia');
    
    const modalElement = document.getElementById('modalDependencia');
    if (modalElement) {
        modalDependencia = new Modal(modalElement);
    }
}

const abrirModalNueva = () => {
    document.getElementById('modalDependenciaLabel').textContent = 'Nueva Dependencia';
    FormDependencias.reset();
    BtnGuardar.classList.remove('d-none');
    BtnModificar.classList.add('d-none');
    modalDependencia.show();
}

const abrirModalEditar = (datos) => {
    document.getElementById('modalDependenciaLabel').textContent = 'Modificar Dependencia';
    
    document.getElementById('dep_llave').value = datos.id;
    document.getElementById('dep_desc_lg').value = datos.descLg;
    document.getElementById('dep_desc_md').value = datos.descMd;
    document.getElementById('dep_desc_ct').value = datos.descCt;
    document.getElementById('dep_clase').value = datos.clase;
    document.getElementById('dep_precio').value = datos.precio || '';
    document.getElementById('dep_ejto').value = datos.ejto || '';
    document.getElementById('dep_latitud').value = datos.latitud || '';
    document.getElementById('dep_longitud').value = datos.longitud || '';
    
    BtnGuardar.classList.add('d-none');
    BtnModificar.classList.remove('d-none');
    modalDependencia.show();
}

const GuardarDependencia = async (event) => {
    event.preventDefault();
    BtnGuardar.disabled = true;

    if (!validarFormulario(FormDependencias, ['dep_llave', 'dep_imagen'])) {
        Swal.fire({
            icon: "info",
            title: "Campos requeridos",
            text: "Complete todos los campos obligatorios marcados con *",
            showConfirmButton: true,
        });
        BtnGuardar.disabled = false;
        return;
    }

    const body = new FormData(FormDependencias);
    const url = '/ProyectoMDEP/mdep/guardarAPI';

    try {
        const respuesta = await fetch(url, { method: 'POST', body });
        const datos = await respuesta.json();

        if (datos.codigo == 1) {
            await Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: datos.mensaje,
                showConfirmButton: true,
            });
            modalDependencia.hide();
            BuscarDependencias();
        } else {
            await Swal.fire({
                icon: "error",
                title: "Error",
                text: datos.mensaje,
                showConfirmButton: true,
            });
        }
    } catch (error) {
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

    const body = new FormData(FormDependencias);
    const url = '/ProyectoMDEP/mdep/modificarAPI';

    try {
        const respuesta = await fetch(url, { method: 'POST', body });
        const datos = await respuesta.json();

        if (datos.codigo == 1) {
            await Swal.fire({
                icon: "success",
                title: "¡Éxito!",
                text: datos.mensaje,
                showConfirmButton: true,
            });
            modalDependencia.hide();
            BuscarDependencias();
        } else {
            await Swal.fire({
                icon: "error", 
                title: "Error",
                text: datos.mensaje,
                showConfirmButton: true,
            });
        }
    } catch (error) {
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
        }
    } catch (error) {
        console.log('Error al cargar dependencias:', error);
    }
}

const DeshabilitarDependencia = async (e) => {
    const id = e.currentTarget.dataset.id;

    const { value: justificacion } = await Swal.fire({
        title: 'Deshabilitar Dependencia',
        text: 'Escriba la justificación para deshabilitar:',
        input: 'textarea',
        inputPlaceholder: 'Justificación detallada...',
        showCancelButton: true,
        confirmButtonText: 'Deshabilitar',
        cancelButtonText: 'Cancelar',
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
                    title: "¡Deshabilitada!",
                    text: datos.mensaje,
                    showConfirmButton: true,
                });
                BuscarDependencias();
            }
        } catch (error) {
            Swal.fire('Error', 'No se pudo deshabilitar la dependencia', 'error');
        }
    }
}

const HabilitarDependencia = async (e) => {
    const id = e.currentTarget.dataset.id;

    const { value: justificacion } = await Swal.fire({
        title: 'Habilitar Dependencia',
        text: 'Escriba la justificación para habilitar:',
        input: 'textarea',
        inputPlaceholder: 'Justificación detallada...',
        showCancelButton: true,
        confirmButtonText: 'Habilitar',
        cancelButtonText: 'Cancelar',
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
                    title: "¡Habilitada!",
                    text: datos.mensaje,
                    showConfirmButton: true,
                });
                BuscarDependencias();
            }
        } catch (error) {
            Swal.fire('Error', 'No se pudo habilitar la dependencia', 'error');
        }
    }
}

const obtenerTextoClase = (clase) => {
    const clases = {
        'A': 'A - Administrativo',
        'O': 'O - Operativo', 
        'D': 'D - Docencia'
    };
    return clases[clase] || clase;
}

const initializeDataTable = () => {
    const tableElement = document.getElementById('TableDependencias');
    if (!tableElement) return;

    datatable = new DataTable('#TableDependencias', {
        language: lenguaje,
        data: [],
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
                    return data ? '<span class="text-success">Sí</span>' : '<span class="text-muted">No</span>';
                }
            },
            {
                title: 'Estado',
                data: 'dep_situacion',
                render: (data) => {
                    return data == 1 ? '<span class="badge bg-success">ACTIVO</span>' : '<span class="badge bg-danger">INACTIVO</span>';
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
                             data-precio="${row.dep_precio || ''}"
                             data-ejto="${row.dep_ejto || ''}"
                             data-latitud="${row.dep_latitud || ''}"
                             data-longitud="${row.dep_longitud || ''}">
                            Modificar 
                         </button>
                         ${btnEstado}
                     </div>`;
                }
            }
        ]
    });
    
    setupDataTableEventListeners();
}

const setupEventListeners = () => {
    if (BtnNuevaDependencia) {
        BtnNuevaDependencia.addEventListener('click', abrirModalNueva);
    }
    
    if (BtnGuardar) {
        BtnGuardar.addEventListener('click', GuardarDependencia);
    }
    
    if (BtnModificar) {
        BtnModificar.addEventListener('click', ModificarDependencia);
    }
}

const setupDataTableEventListeners = () => {
    if (datatable) {
        datatable.on('click', '.deshabilitar', DeshabilitarDependencia);
        datatable.on('click', '.habilitar', HabilitarDependencia);
        datatable.on('click', '.modificar', (e) => {
            const datos = e.currentTarget.dataset;
            abrirModalEditar(datos);
        });
    }
}