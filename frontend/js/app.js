// API PHP para prototipo desarrollado con XAMPP
const API = "../backend/api.php?resource=productos";

const form = document.getElementById("productForm");
const mensajeEl = document.getElementById("mensaje");
const productsGrid = document.getElementById("productsGrid");
const inventoryCards = document.getElementById("inventoryCards");
const statsEl = document.getElementById("stats");
const statusTabs = document.querySelectorAll(".status-tab");

let products = [];
let currentStatus = "all";
let searchQuery = "";

// ── Tabs ──────────────────────────────────────────────────
statusTabs.forEach(tab => {
    tab.addEventListener("click", () => {
        statusTabs.forEach(t => {
            t.classList.remove("active");
            t.setAttribute("aria-selected", "false");
        });
        tab.classList.add("active");
        tab.setAttribute("aria-selected", "true");
        currentStatus = tab.getAttribute("data-status");
        loadProducts();
    });
});

// ── Búsqueda ──────────────────────────────────────────────
document.getElementById("searchInput").addEventListener("input", (e) => {
    searchQuery = e.target.value.toLowerCase();
    renderProducts();
});

// ── Cálculo valor final ───────────────────────────────────
const valorNetoInput = document.getElementById("valorNeto");
const impuestoInput  = document.getElementById("impuesto");
const valorFinalInput = document.getElementById("valorFinal");

function calculateFinalValue() {
    const neto  = Number(valorNetoInput.value) || 0;
    const imp   = Number(impuestoInput.value)  || 0;
    const final = neto + (neto * imp / 100);
    valorFinalInput.value = final > 0
        ? "$" + Math.round(final).toLocaleString("es-CL")
        : "";
}

if (valorNetoInput && impuestoInput) {
    valorNetoInput.addEventListener("input", calculateFinalValue);
    impuestoInput.addEventListener("input",  calculateFinalValue);
}

// ── Formulario ────────────────────────────────────────────
form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const nombre            = document.getElementById("nombre").value.trim();
    const cantidad          = Number(document.getElementById("cantidad").value);
    const vencimiento       = document.getElementById("vencimiento").value;
    const fecha_elaboracion = document.getElementById("fechaElaboracion")
                                ? document.getElementById("fechaElaboracion").value
                                : null;
    const valor_neto = valorNetoInput ? Number(valorNetoInput.value) : 0;
    const impuesto   = impuestoInput  ? Number(impuestoInput.value)  : 0;

    try {
        if (!nombre || !cantidad || !vencimiento)
            throw new Error("Completa todos los campos obligatorios");

        const token = localStorage.getItem('token');
        const res = await fetch(API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": `Bearer ${token}`
            },
            body: JSON.stringify({ nombre, cantidad, vencimiento, fecha_elaboracion, valor_neto, impuesto })
        });

        if (res.status === 401) {
            logout();
            return;
        }

        if (!res.ok) {
            const data = await res.json();
            throw new Error(data.error || "Error al guardar el producto");
        }

        showMessage("Producto agregado con éxito", "success");
        form.reset();
        if (impuestoInput) impuestoInput.value = 19;
        if (valorFinalInput) valorFinalInput.value = "";

        await loadProducts();

    } catch (error) {
        showMessage(error.message, "error");
    }
});

// ── Helpers ───────────────────────────────────────────────
function showMessage(text, type = "success") {
    mensajeEl.textContent = text;
    mensajeEl.className   = `alert ${type}`;
    clearTimeout(mensajeEl._timer);
    mensajeEl._timer = setTimeout(() => {
        mensajeEl.textContent = "";
        mensajeEl.className   = "alert";
    }, 3500);
}

function showPageAlert(text, type = "warning") {
    const pageAlert     = document.getElementById("pageAlert");
    const pageAlertText = document.getElementById("pageAlertText");
    pageAlertText.textContent = text;
    pageAlert.className = `page-alert ${type} visible`;
    clearTimeout(window.pageAlertTimeout);
    window.pageAlertTimeout = setTimeout(() => {
        pageAlert.className = "page-alert hidden";
    }, 4200);
}

document.getElementById("pageAlertClose").addEventListener("click", () => {
    document.getElementById("pageAlert").className = "page-alert hidden";
    clearTimeout(window.pageAlertTimeout);
});

function checkExpiryAlerts(products) {
    const exp  = products.filter(p => p.status === "vencido");
    const soon = products.filter(p => p.status === "por_vencer");
    if (exp.length > 0) {
        showPageAlert(`¡Atención! ${exp.length} producto(s) vencido(s) detectado(s).`, "error");
    } else if (soon.length > 0) {
        showPageAlert(`Aviso: ${soon.length} producto(s) por vencer en los próximos 7 días.`, "warning");
    }
}

function getStatus(product) {
    const now     = new Date();
    const dueDate = new Date(product.vencimiento + "T23:59:59");
    const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
    if (diffDays < 0)  return "vencido";
    if (diffDays <= 7) return "por_vencer";
    return "vigente";
}

function isCritical(product, stockCritico = 5) {
    const status = getStatus(product);
    return (status === "por_vencer" || status === "vencido") && product.cantidad <= stockCritico;
}

function fmtDate(d) {
    if (!d || d === "-") return "—";
    const parts = d.split("-");
    if (parts.length !== 3) return d;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

function fmtMoney(neto, imp) {
    if (!neto || neto <= 0) return "—";
    const final = Math.round(Number(neto) + (Number(neto) * Number(imp || 0) / 100));
    return "$" + final.toLocaleString("es-CL");
}

function statusLabel(s) {
    return { vigente: "Vigente", por_vencer: "Por vencer", vencido: "Vencido" }[s] || s;
}

function esc(str) {
    return String(str)
        .replace(/&/g, "&amp;").replace(/</g, "&lt;")
        .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

// ── Eliminar ──────────────────────────────────────────────
async function deleteProduct(id) {
    if (!confirm("¿Eliminar este producto del inventario?")) return;

    const token = localStorage.getItem('token');
    const res = await fetch(`../backend/api.php?resource=productos&id=${id}`, { 
        method: "DELETE",
        headers: { "Authorization": `Bearer ${token}` }
    });

    if (res.status === 401) { logout(); return; }

    if (res.ok) {
        showMessage("Producto eliminado", "success");
        await loadProducts();
    } else {
        const data = await res.json();
        showMessage(data.error || "No se pudo eliminar", "error");
    }
}

// ── Cargar desde API ──────────────────────────────────────
async function loadProducts() {
    const token = localStorage.getItem('token');
    const res = await fetch(API, {
        headers: { "Authorization": `Bearer ${token}` }
    });

    if (res.status === 401) { logout(); return; }

    products = await res.json();
    renderProducts();
    loadTopExpiredChart();
}

// ── Render principal ──────────────────────────────────────
function renderProducts() {
    const stockCritico = Number(document.getElementById("stockCritico").value) || 5;

    // Enriquecer productos con status y días restantes
    const enriched = products.map(p => ({
        ...p,
        status: getStatus(p),
        diasRestantes: Math.ceil(
            (new Date(p.vencimiento + "T23:59:59") - new Date()) / (1000 * 60 * 60 * 24)
        )
    }));

    // Filtrar
    const filtered = enriched.filter(p => {
        const matchStatus = currentStatus === "all" || p.status === currentStatus;
        const matchSearch = searchQuery === "" || p.nombre.toLowerCase().includes(searchQuery);
        return matchStatus && matchSearch;
    });

    // Contadores
    const summary = { total: 0, vigente: 0, por_vencer: 0, vencido: 0, critic: 0 };
    filtered.forEach(p => {
        summary.total++;
        summary[p.status]++;
        if (isCritical(p, stockCritico)) summary.critic++;
    });

    // ── Renderizar TABLA (PC / tablet) ───────────────────
    renderTable(filtered, stockCritico);

    // ── Renderizar CARDS (móvil) ─────────────────────────
    renderCards(filtered, stockCritico);

    // ── Dashboard ─────────────────────────────────────────
    document.getElementById("totalCount").textContent    = summary.total;
    document.getElementById("vigenteCount").textContent  = summary.vigente;
    document.getElementById("porVencerCount").textContent= summary.por_vencer;
    document.getElementById("vencidoCount").textContent  = summary.vencido;
    document.getElementById("criticCount").textContent   = summary.critic;

    const max = Math.max(summary.total, 1);
    document.getElementById("barVigente").style.width   = `${(summary.vigente   / max) * 100}%`;
    document.getElementById("barPorVencer").style.width = `${(summary.por_vencer/ max) * 100}%`;
    document.getElementById("barVencido").style.width   = `${(summary.vencido   / max) * 100}%`;

    statsEl.innerHTML = `
        <span>Total: ${summary.total}</span>
        <span>Vigentes: ${summary.vigente}</span>
        <span>Por vencer: ${summary.por_vencer}</span>
        <span>Vencidos: ${summary.vencido}</span>
        <span>Críticos: ${summary.critic}</span>`;

    checkExpiryAlerts(filtered);
}

// ── Tabla ─────────────────────────────────────────────────
function renderTable(filtered, stockCritico) {
    productsGrid.innerHTML = "";

    if (!filtered.length) {
        const emptyRow = document.createElement("tr");
        emptyRow.innerHTML = `<td colspan="7" class="empty-row">No hay productos para mostrar.</td>`;
        productsGrid.appendChild(emptyRow);
        return;
    }

    filtered.forEach(product => {
        const dias    = product.diasRestantes;
        const dayText = dias < 0 ? `${Math.abs(dias)}d atrás` : `${dias}d`;
        const critico = isCritical(product, stockCritico);

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td>
                ${esc(product.nombre)}
                ${critico ? '<br><small style="color:#f97316;font-size:.72rem">⚠ Stock crítico</small>' : ''}
            </td>
            <td>${product.cantidad}</td>
            <td>${product.vencimiento} <span class="small-text">(${dayText})</span></td>
            <td>${product.fecha_elaboracion || "—"}</td>
            <td>${fmtMoney(product.valor_neto, product.impuesto)}</td>
            <td><span class="status-pill status-${product.status}">${statusLabel(product.status)}</span></td>
            <td><button class="btn-danger small" onclick="deleteProduct(${product.id})">Eliminar</button></td>
        `;

        tr.classList.add("row-enter");
        productsGrid.appendChild(tr);
        requestAnimationFrame(() => tr.classList.add("row-enter-active"));
    });
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('usuario');
    window.location.href = 'login.php';
}

function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = 'login.php';
    } else {
        loadProducts();
    }
}

// Se ejecuta al cargar la página Y si el navegador restaura la página del bfcache (botón ←)
window.addEventListener('pageshow', () => {
    checkAuth();
});

// ── Cards (móvil) ─────────────────────────────────────────
function renderCards(filtered, stockCritico) {
    if (!inventoryCards) return;
    inventoryCards.innerHTML = "";

    if (!filtered.length) {
        inventoryCards.innerHTML = `
            <div style="text-align:center;padding:2rem 1rem;color:#7090b8;font-style:italic;font-size:.9rem;">
                No hay productos para mostrar.
            </div>`;
        return;
    }

    filtered.forEach(product => {
        const dias    = product.diasRestantes;
        const dayText = dias < 0 ? `${Math.abs(dias)} días atrás` : `${dias} días`;
        const critico = isCritical(product, stockCritico);

        const card = document.createElement("div");
        card.className = "inv-card";
        card.innerHTML = `
            <div class="inv-card-header">
                <div>
                    <div class="inv-card-name">${esc(product.nombre)}</div>
                    ${critico
                        ? '<div style="font-size:.72rem;color:#f97316;margin-top:2px">⚠ Stock crítico</div>'
                        : ''}
                </div>
                <span class="status-pill status-${product.status}">${statusLabel(product.status)}</span>
            </div>
            <div class="inv-card-meta">
                <div class="inv-card-meta-item">
                    <span class="meta-label">Cantidad</span>
                    <span class="meta-val">${product.cantidad}</span>
                </div>
                <div class="inv-card-meta-item">
                    <span class="meta-label">Valor final</span>
                    <span class="meta-val">${fmtMoney(product.valor_neto, product.impuesto)}</span>
                </div>
                <div class="inv-card-meta-item">
                    <span class="meta-label">Vencimiento</span>
                    <span class="meta-val">${fmtDate(product.vencimiento)} <span style="font-size:.72rem;opacity:.75">(${dayText})</span></span>
                </div>
                <div class="inv-card-meta-item">
                    <span class="meta-label">Elaboración</span>
                    <span class="meta-val">${fmtDate(product.fecha_elaboracion)}</span>
                </div>
            </div>
            <div class="inv-card-footer">
                <span style="font-size:.75rem;color:#6a8ab0;">#${product.id}</span>
                <button class="btn-danger small" onclick="deleteProduct(${product.id})">Eliminar</button>
            </div>
        `;

        card.classList.add("row-enter");
        inventoryCards.appendChild(card);
        requestAnimationFrame(() => card.classList.add("row-enter-active"));
    });
}

// ── Temas ──────────────────────────────────────────────────
const themeToggle = document.getElementById("themeToggle");
if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        let currentTheme = document.documentElement.getAttribute("data-theme");
        if (currentTheme === "dark") {
            document.documentElement.setAttribute("data-theme", "light");
        } else {
            document.documentElement.setAttribute("data-theme", "dark");
        }
        currentTheme = document.documentElement.getAttribute("data-theme");
        themeToggle.textContent = currentTheme === "dark" ? "🌙" : "☀️";
    });
}

// ── Gráfico Top Vencidos ───────────────────────────────────
async function loadTopExpiredChart() {
    try {
        const canvasElement = document.getElementById('topExpiredChart');
        if (!canvasElement) return;
        
        if (typeof Chart === 'undefined') return;
        
        const token = localStorage.getItem('token');
        const res = await fetch("../backend/api.php?resource=top-expired", {
            headers: { "Authorization": `Bearer ${token}` }
        });
        
        if (res.status === 401) { logout(); return; }
        if (!res.ok) return;
        
        const topExpired = await res.json();
        if (!topExpired || topExpired.length === 0) return;

        const nombres = topExpired.map(p => p.nombre.substring(0, 20));
        const cantidades = topExpired.map(p => p.cantidad);
        
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
                            font: { size: 12 },
                            color: getComputedStyle(document.documentElement).getPropertyValue('--text').trim()
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error("Error al cargar el gráfico:", error);
    }
}
