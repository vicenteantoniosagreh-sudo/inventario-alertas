const API = "http://localhost:3000/products";

const form = document.getElementById("productForm");
const mensajeEl = document.getElementById("mensaje");
const productsGrid = document.getElementById("productsGrid");
const statsEl = document.getElementById("stats");
const filterStatus = document.getElementById("filterStatus");

let products = [];

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

    const res = await fetch(`${API}/${id}`, { method: "DELETE" });
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

    const statusFilter = filterStatus.value;
    const stockCritico = Number(document.getElementById("stockCritico").value) || 5;

    productsGrid.innerHTML = "";

    const summary = { total: 0, vigente: 0, por_vencer: 0, vencido: 0, critic: 0 };

    products
        .map((p) => ({ ...p, status: getStatus(p), diasRestantes: Math.ceil((new Date(p.vencimiento + "T23:59:59") - new Date()) / (1000 * 60 * 60 * 24)) }))
        .filter((p) => statusFilter === "all" || p.status === statusFilter)
        .forEach((product) => {
            summary.total += 1;
            summary[product.status] += 1;
            if (isCritical(product, stockCritico)) summary.critic += 1;

            const card = document.createElement("article");
            card.className = `product-card status-${product.status} ${isCritical(product, stockCritico) ? "critical" : ""}`;

            const badge = product.status === "vigente" ? "Vigente" : product.status === "por_vencer" ? "Por vencer" : "Vencido";
            const dias = product.diasRestantes;
            const dayText = dias < 0 ? `${Math.abs(dias)} días atrás` : `${dias} días`;

            card.innerHTML = `
                <h3>${product.nombre}</h3>
                <p>Cantidad: ${product.cantidad}</p>
                <p>Vence: ${product.vencimiento} (${dayText})</p>
                <p class="status-pill status-${product.status}">${badge}</p>
                <div class="actions">
                    <button class="btn-danger" onclick="deleteProduct(${product.id})">Eliminar</button>
                </div>
            `;

            productsGrid.appendChild(card);
        });

    statsEl.innerHTML = `<span>Total: ${summary.total}</span><span>Vigentes: ${summary.vigente}</span><span>Por vencer: ${summary.por_vencer}</span><span>Vencidos: ${summary.vencido}</span><span>Críticos: ${summary.critic}</span>`;
}

loadProducts();
