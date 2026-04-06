// API PHP para prototipo desarrollado con XAMPP
const API = "../backend/api.php?resource=productos";

const form = document.getElementById("productForm");
const mensajeEl = document.getElementById("mensaje");
const productsGrid = document.getElementById("productsGrid");
const statsEl = document.getElementById("stats");
const statusTabs = document.querySelectorAll(".status-tab");

let products = [];
let currentStatus = "all";
let searchQuery = "";

statusTabs.forEach(tab => {
    tab.addEventListener("click", () => {
        statusTabs.forEach(t => t.classList.remove("active"));
        tab.classList.add("active");
        currentStatus = tab.getAttribute("data-status");
        loadProducts();
    });
});

document.getElementById("searchInput").addEventListener("input", (e) => {
    searchQuery = e.target.value.toLowerCase();
    renderProducts();
});

const valorNetoInput = document.getElementById("valorNeto");
const impuestoInput = document.getElementById("impuesto");
const valorFinalInput = document.getElementById("valorFinal");

function calculateFinalValue() {
    const neto = Number(valorNetoInput.value) || 0;
    const imp = Number(impuestoInput.value) || 0;
    const final = neto + (neto * imp / 100);
    valorFinalInput.value = final > 0 ? Math.round(final) : "";
}

if (valorNetoInput && impuestoInput) {
    valorNetoInput.addEventListener("input", calculateFinalValue);
    impuestoInput.addEventListener("input", calculateFinalValue);
}

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const nombre = document.getElementById("nombre").value.trim();
    const cantidad = Number(document.getElementById("cantidad").value);
    const vencimiento = document.getElementById("vencimiento").value;
    const fecha_elaboracion = document.getElementById("fechaElaboracion") ? document.getElementById("fechaElaboracion").value : null;
    const valor_neto = valorNetoInput ? Number(valorNetoInput.value) : 0;
    const impuesto = impuestoInput ? Number(impuestoInput.value) : 0;

    try {
        if (!nombre || !cantidad || !vencimiento) throw new Error("Completa todos los campos obligatorios");

        const res = await fetch(API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ nombre, cantidad, vencimiento, fecha_elaboracion, valor_neto, impuesto })
        });

        if (!res.ok) {
            const data = await res.json();
            throw new Error(data.error || "Error al guardar el producto");
        }

        showMessage("Producto agregado con éxito", "success");
        form.reset();

        await loadProducts();
    } catch (error) {
        showMessage(error.message, "error");
    }
});

// filterStatus not used, mantenemos filtrado con tabs

function showMessage(text, type = "success") {
    mensajeEl.textContent = text;
    mensajeEl.className = `alert ${type}`;

    setTimeout(() => {
        mensajeEl.textContent = "";
        mensajeEl.className = "alert";
    }, 3500);
}

function showPageAlert(text, type = "warning") {
    const pageAlert = document.getElementById('pageAlert');
    const pageAlertText = document.getElementById('pageAlertText');
    pageAlertText.textContent = text;
    pageAlert.className = `page-alert ${type} visible`;

    clearTimeout(window.pageAlertTimeout);
    window.pageAlertTimeout = setTimeout(() => {
        pageAlert.className = 'page-alert hidden';
    }, 4200);
}

document.getElementById('pageAlertClose').addEventListener('click', () => {
    const pageAlert = document.getElementById('pageAlert');
    pageAlert.className = 'page-alert hidden';
    clearTimeout(window.pageAlertTimeout);
});

function checkExpiryAlerts(products) {
    const exp = products.filter(p => p.status === 'vencido');
    const soon = products.filter(p => p.status === 'por_vencer');

    if (exp.length > 0) {
        showPageAlert(`¡Atención! ${exp.length} producto(s) vencido(s) detectado(s).`, 'error');
    } else if (soon.length > 0) {
        showPageAlert(`Aviso: ${soon.length} producto(s) por vencer en los próximos 7 días.`, 'warning');
    }
}

function getStatus(product) {
    const now = new Date();
    const dueDate = new Date(product.vencimiento + "T23:59:59");
    const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));

    if (diffDays < 0) return "vencido";
    if (diffDays <= 7) return "por_vencer";
    return "vigente";
}

function isCritical(product, stockCritico = 5) {
    const status = getStatus(product);
    return (status === "por_vencer" || status === "vencido") && product.cantidad <= stockCritico;
}

async function deleteProduct(id) {
    const clave = confirm("¿Eliminar este producto del inventario?");
    if (!clave) return;

    const res = await fetch(`../backend/api.php?resource=productos&id=${id}`, { method: "DELETE" });
    if (res.ok) {
        showMessage("Producto eliminado", "success");
        await loadProducts();
    } else {
        const data = await res.json();
        showMessage(data.error || "No se pudo eliminar", "error");
    }
}

async function loadProducts() {
    const res = await fetch(API);
    products = await res.json();
    renderProducts();
    loadTopExpiredChart();
}

function renderProducts() {
    const stockCritico = Number(document.getElementById("stockCritico").value) || 5;
    productsGrid.innerHTML = "";

    const summary = { total: 0, vigente: 0, por_vencer: 0, vencido: 0, critic: 0 };

    const filtered = products
        .map((p) => ({ ...p, status: getStatus(p), diasRestantes: Math.ceil((new Date(p.vencimiento + "T23:59:59") - new Date()) / (1000 * 60 * 60 * 24)) }))
        .filter((p) => {
            const matchStatus = currentStatus === "all" || p.status === currentStatus;
            const matchSearch = searchQuery === "" || p.nombre.toLowerCase().includes(searchQuery);
            return matchStatus && matchSearch;
        });

    filtered.forEach((product) => {
        summary.total += 1;
        summary[product.status] += 1;
        if (isCritical(product, stockCritico)) summary.critic += 1;

        const tr = document.createElement("tr");
        const badge = product.status === "vigente" ? "Vigente" : product.status === "por_vencer" ? "Por vencer" : "Vencido";
        const dias = product.diasRestantes;
        const dayText = dias < 0 ? `${Math.abs(dias)} días atrás` : `${dias} días`;

        const neto = Number(product.valor_neto) || 0;
        const imp = Number(product.impuesto) || 0;
        const finalPrice = neto > 0 ? "$" + Math.round(neto + (neto * imp / 100)) : "-";
        const elaboracionDate = product.fecha_elaboracion || '-';

        tr.innerHTML = `
            <td>${product.nombre}</td>
            <td>${product.cantidad}</td>
            <td>${product.vencimiento} <span class="small-text">(${dayText})</span></td>
            <td>${elaboracionDate}</td>
            <td>${finalPrice}</td>
            <td><span class="status-pill status-${product.status}">${badge}</span></td>
            <td><button class="btn-danger small" onclick="deleteProduct(${product.id})">Eliminar</button></td>
        `;

        tr.classList.add('row-enter');
        productsGrid.appendChild(tr);
        window.requestAnimationFrame(() => {
            tr.classList.add('row-enter-active');
        });
    });

    if (filtered.length === 0) {
        const emptyRow = document.createElement("tr");
        emptyRow.innerHTML = `<td colspan="5" class="empty-row">No hay productos para mostrar.</td>`;
        productsGrid.appendChild(emptyRow);
    }

    statsEl.innerHTML = `<span>Total: ${summary.total}</span><span>Vigentes: ${summary.vigente}</span><span>Por vencer: ${summary.por_vencer}</span><span>Vencidos: ${summary.vencido}</span><span>Críticos: ${summary.critic}</span>`;

    // Actualiza cards y gráfico
    document.getElementById('totalCount').textContent = summary.total;
    document.getElementById('vigenteCount').textContent = summary.vigente;
    document.getElementById('porVencerCount').textContent = summary.por_vencer;
    document.getElementById('vencidoCount').textContent = summary.vencido;
    document.getElementById('criticCount').textContent = summary.critic;

    checkExpiryAlerts(filtered);

    const max = Math.max(summary.total, 1);
    document.getElementById('barVigente').style.width = `${(summary.vigente / max) * 100}%`;
    document.getElementById('barPorVencer').style.width = `${(summary.por_vencer / max) * 100}%`;
    document.getElementById('barVencido').style.width = `${(summary.vencido / max) * 100}%`;
}

async function loadTopExpiredChart() {
    try {
        console.log("Iniciando carga del gráfico...");
        
        const canvasElement = document.getElementById('topExpiredChart');
        if (!canvasElement) {
            console.error("❌ Canvas no encontrado en el DOM");
            return;
        }
        console.log("✓ Canvas encontrado");
        
        if (typeof Chart === 'undefined') {
            console.error("❌ Chart.js no está cargado");
            return;
        }
        console.log("✓ Chart.js está disponible");
        
        const res = await fetch("../backend/api.php?resource=top-expired");
        console.log("Respuesta del servidor:", res.status);
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error("Error del servidor:", errorText);
            throw new Error("Error al cargar datos del gráfico: " + res.status);
        }
        
        const topExpired = await res.json();
        console.log("Datos recibidos:", topExpired);
        
        if (!topExpired || topExpired.length === 0) {
            console.warn("⚠️ No hay datos para el gráfico");
            return;
        }

        const nombres = topExpired.map(p => p.nombre.substring(0, 20));
        const cantidades = topExpired.map(p => p.cantidad);
        
        console.log("Nombres:", nombres);
        console.log("Cantidades:", cantidades);
        
        const colors = [
            'rgba(220, 53, 69, 0.7)',
            'rgba(230, 70, 80, 0.7)',
            'rgba(255, 100, 100, 0.7)',
            'rgba(255, 140, 100, 0.7)',
            'rgba(255, 165, 100, 0.7)'
        ];
        
        const borderColors = [
            'rgb(220, 53, 69)',
            'rgb(230, 70, 80)',
            'rgb(255, 100, 100)',
            'rgb(255, 140, 100)',
            'rgb(255, 165, 100)'
        ];

        const ctx = canvasElement.getContext('2d');
        
        if (window.topExpiredChartInstance) {
            window.topExpiredChartInstance.destroy();
        }
        
        console.log("Creando gráfico...");
        window.topExpiredChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: nombres,
                datasets: [{
                    data: cantidades,
                    backgroundColor: colors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' unidades';
                            }
                        }
                    }
                }
            }
        });
        
        console.log("✓ Gráfico creado exitosamente");
    } catch (error) {
        console.error("❌ Error al cargar el gráfico:", error);
    }
}

loadProducts();
loadTopExpiredChart();
