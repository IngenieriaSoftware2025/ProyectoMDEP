    import { Modal } from "bootstrap";
    import Swal from "sweetalert2";

    const BtnVolver = document.getElementById('BtnVolver');
    const BtnActualizar = document.getElementById('BtnActualizar');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const modalCoordenadas = new Modal(document.getElementById('modalCoordenadas'));

    const totalDependencias = document.getElementById('totalDependencias');
    const conUbicacion = document.getElementById('conUbicacion');
    const conImagen = document.getElementById('conImagen');
    const activas = document.getElementById('activas');

    let mapa;
    let marcadores = [];

    const guatemalaCenter = [15.7835, -90.2308];

    const inicializarMapa = () => {
        if (typeof L === 'undefined') {
            console.error('Leaflet no está cargado');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La biblioteca de mapas no se pudo cargar. Verifique su conexión a internet.'
            });
            return;
        }

        const mapaContainer = document.getElementById('mapa');
        if (!mapaContainer) {
            console.error('Contenedor del mapa no encontrado');
            return;
        }

        try {
            mapa = L.map('mapa').setView(guatemalaCenter, 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                minZoom: 6
            }).addTo(mapa);

            mapa.on('click', function(e) {
                mostrarCoordenadas(e.latlng.lat, e.latlng.lng);
            });

            console.log('Mapa inicializado correctamente');
            return true;
        } catch (error) {
            console.error('Error al crear mapa:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error del Mapa',
                text: 'No se pudo inicializar el mapa: ' + error.message
            });
            return false;
        }
    }

    const mostrarCoordenadas = (lat, lng) => {
        document.getElementById('coordLatitud').value = lat.toFixed(6);
        document.getElementById('coordLongitud').value = lng.toFixed(6);
        modalCoordenadas.show();
    }

    const copiarCoordenadas = async () => {
        const lat = document.getElementById('coordLatitud').value;
        const lng = document.getElementById('coordLongitud').value;
        const texto = `Latitud: ${lat}, Longitud: ${lng}`;

        try {
            await navigator.clipboard.writeText(texto);
            Swal.fire({
                icon: 'success',
                title: '¡Copiado!',
                text: 'Coordenadas copiadas al portapapeles',
                timer: 1500,
                showConfirmButton: false
            });
            modalCoordenadas.hide();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron copiar las coordenadas'
            });
        }
    }

    const mostrarLoading = (mostrar = true) => {
        loadingOverlay.style.display = mostrar ? 'flex' : 'none';
    }

    const limpiarMarcadores = () => {
        marcadores.forEach(marcador => {
            mapa.removeLayer(marcador);
        });
        marcadores = [];
    }

    const crearIconoPersonalizado = (tipoClase, tieneImagen = false) => {
        const colores = {
            'A': '#007bff', 
            'O': '#28a745', 
            'D': '#ffc107', 
            'R': '#dc3545'  
        };

        const color = colores[tipoClase] || '#6c757d';
        const tamaño = tieneImagen ? 35 : 25;

        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                background-color: ${color};
                width: ${tamaño}px;
                height: ${tamaño}px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                font-size: 12px;
            ">${tipoClase}${tieneImagen ? '📷' : ''}</div>`,
            iconSize: [tamaño, tamaño],
            iconAnchor: [tamaño/2, tamaño/2]
        });
    }

    const cargarDependencias = async () => {
        mostrarLoading(true);
        
        try {
            const respuesta = await fetch('/ProyectoMDEP/mdep/buscarAPI');
            const datos = await respuesta.json();

            if (datos.codigo == 1) {
                procesarDependencias(datos.data);
            } else {
                throw new Error(datos.mensaje || 'Error al obtener dependencias');
            }
        } catch (error) {
            console.error('Error al cargar dependencias:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las dependencias: ' + error.message
            });
        } finally {
            mostrarLoading(false);
        }
    }

    const procesarDependencias = (dependencias) => {
        limpiarMarcadores();

        let stats = {
            total: dependencias.length,
            conUbicacion: 0,
            conImagen: 0,
            activas: 0
        };
        dependencias.forEach(dep => {
            if (dep.dep_situacion == 1) stats.activas++;
            if (dep.dep_ruta_logo) stats.conImagen++;
            
            if (dep.dep_latitud && dep.dep_longitud) {
                stats.conUbicacion++;
                
                const lat = parseFloat(dep.dep_latitud);
                const lng = parseFloat(dep.dep_longitud);
                
                if (isNaN(lat) || isNaN(lng)) {
                    console.warn(`Coordenadas inválidas para dependencia ${dep.dep_llave}`);
                    return;
                }

                const icono = crearIconoPersonalizado(dep.dep_clase, !!dep.dep_ruta_logo);
                
                const marcador = L.marker([lat, lng], { icon: icono })
                    .addTo(mapa)
                    .bindPopup(crearPopupContent(dep));

                marcadores.push(marcador);
            }
        });

        actualizarEstadisticas(stats);

        if (marcadores.length > 0) {
            const grupo = new L.featureGroup(marcadores);
            mapa.fitBounds(grupo.getBounds(), { padding: [20, 20] });
        }

        console.log(`Procesadas ${stats.total} dependencias, ${stats.conUbicacion} con ubicación`);
    }

    const crearPopupContent = (dependencia) => {
        const estadoBadge = dependencia.dep_situacion == 1 ? 
            '<span class="badge bg-success">ACTIVO</span>' : 
            '<span class="badge bg-danger">INACTIVO</span>';
            
        const imagenInfo = dependencia.dep_ruta_logo ? 
            '<span class="badge bg-info">Con Logo</span>' : 
            '<span class="badge bg-secondary">Sin Logo</span>';

        const claseTexto = {
            'A': 'Administrativo',
            'O': 'Operativo',
            'D': 'Docencia', 
            'R': 'Rescate'
        }[dependencia.dep_clase] || dependencia.dep_clase;

        return `
            <div class="popup-content" style="min-width: 200px;">
                <h6 class="fw-bold text-primary mb-2">${dependencia.dep_desc_lg}</h6>
                <p class="mb-1"><strong>Descripción:</strong> ${dependencia.dep_desc_md}</p>
                <p class="mb-1"><strong>Abrev:</strong> ${dependencia.dep_desc_ct}</p>
                <p class="mb-1"><strong>Clase:</strong> ${claseTexto}</p>
                <p class="mb-2"><strong>Coordenadas:</strong><br>
                <small>Lat: ${dependencia.dep_latitud}<br>Lng: ${dependencia.dep_longitud}</small>
                </p>
                <div class="d-flex gap-1 flex-wrap">
                    ${estadoBadge}
                    ${imagenInfo}
                </div>
            </div>
        `;
    }

    const actualizarEstadisticas = (stats) => {
        totalDependencias.textContent = stats.total;
        conUbicacion.textContent = stats.conUbicacion;
        conImagen.textContent = stats.conImagen;
        activas.textContent = stats.activas;
    }

    const volverPrincipal = () => {
        window.location.href = '/ProyectoMDEP/mdep';
    }

    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, inicializando...');
        
        try {
            inicializarMapa();
            console.log('Mapa inicializado correctamente');
        } catch (error) {
            console.error('Error al inicializar mapa:', error);
        }
        
        setTimeout(() => {
            cargarDependencias();
        }, 500);
        
        if (BtnVolver) {
            BtnVolver.addEventListener('click', volverPrincipal);
            console.log('Event listener BtnVolver agregado');
        } else {
            console.warn('BtnVolver no encontrado');
        }
        
        if (BtnActualizar) {
            BtnActualizar.addEventListener('click', cargarDependencias);
            console.log('Event listener BtnActualizar agregado');
        } else {
            console.warn('BtnActualizar no encontrado');
        }
        
        const btnCopiar = document.getElementById('copiarCoordenadas');
        if (btnCopiar) {
            btnCopiar.addEventListener('click', copiarCoordenadas);
            console.log('Event listener copiar coordenadas agregado');
        } else {
            console.warn('Botón copiar coordenadas no encontrado');
        }
        
        console.log('Página de ubicaciones inicializada');
    });