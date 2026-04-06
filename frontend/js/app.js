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
}

function renderProducts() {
    const stockCritico = Number(document.getElementById("stockCritico").value) || 5;
    productsGrid.innerHTML = "";

    const summary = { total: 0, vigente: 0, por_vencer: 0, vencido: 0, critic: 0, loss: 0 };

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

        const neto = Number(product.valor_neto) || 0;
        const imp = Number(product.impuesto) || 0;
        const productFinal = neto + (neto * imp / 100);
        if (product.status === 'vencido') {
            summary.loss += productFinal * Number(product.cantidad);
        }

        const tr = document.createElement("tr");
        const badge = product.status === "vigente" ? "Vigente" : product.status === "por_vencer" ? "Por vencer" : "Vencido";
        const dias = product.diasRestantes;
        const dayText = dias < 0 ? `${Math.abs(dias)} días atrás` : `${dias} días`;

        const finalPrice = neto > 0 ? "$" + Math.round(productFinal) : "-";
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

    statsEl.innerHTML = `<span>Total: ${summary.total}</span><span>Vigentes: ${summary.vigente}</span><span>Por vencer: ${summary.por_vencer}</span><span>Vencidos: ${summary.vencido}</span><span>Críticos: ${summary.critic}</span><span>Pérdida total venc.: $${summary.loss.toLocaleString()}</span>`;

    // Actualiza cards y gráfico
    document.getElementById('totalCount').textContent = summary.total;
    document.getElementById('vigenteCount').textContent = summary.vigente;
    document.getElementById('porVencerCount').textContent = summary.por_vencer;
    document.getElementById('vencidoCount').textContent = summary.vencido;
    document.getElementById('criticCount').textContent = summary.critic;
    document.getElementById('lossCount').textContent = `$${summary.loss.toLocaleString()}`;

    checkExpiryAlerts(filtered);

    const max = Math.max(summary.total, 1);
    document.getElementById('barVigente').style.width = `${(summary.vigente / max) * 100}%`;
    document.getElementById('barPorVencer').style.width = `${(summary.por_vencer / max) * 100}%`;
    document.getElementById('barVencido').style.width = `${(summary.vencido / max) * 100}%`;
}

loadProducts();
