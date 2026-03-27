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

form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const nombre = document.getElementById("nombre").value.trim();
    const cantidad = Number(document.getElementById("cantidad").value);
    const vencimiento = document.getElementById("vencimiento").value;

    try {
        if (!nombre || !cantidad || !vencimiento) throw new Error("Completa todos los campos");

        const res = await fetch(API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ nombre, cantidad, vencimiento })
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

filterStatus.addEventListener("change", () => loadProducts());

function showMessage(text, type = "success") {
    mensajeEl.textContent = text;
    mensajeEl.className = `alert ${type}`;

    setTimeout(() => {
        mensajeEl.textContent = "";
        mensajeEl.className = "alert";
    }, 2500);
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

        tr.innerHTML = `
            <td>${product.nombre}</td>
            <td>${product.cantidad}</td>
            <td>${product.vencimiento} <span class="small-text">(${dayText})</span></td>
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

    const max = Math.max(summary.total, 1);
    document.getElementById('barVigente').style.width = `${(summary.vigente / max) * 100}%`;
    document.getElementById('barPorVencer').style.width = `${(summary.por_vencer / max) * 100}%`;
    document.getElementById('barVencido').style.width = `${(summary.vencido / max) * 100}%`;
}

loadProducts();
