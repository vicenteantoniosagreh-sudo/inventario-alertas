const API = "http://localhost:3000/products";

const form = document.getElementById("productForm");
const lista = document.getElementById("lista");

form.addEventListener("submit", async (e) => {

    e.preventDefault();

    const nombre = document.getElementById("nombre").value;
    const cantidad = document.getElementById("cantidad").value;
    const vencimiento = document.getElementById("vencimiento").value;

    await fetch(API, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ nombre, cantidad, vencimiento })
    });

    cargarProductos();
});

async function cargarProductos() {

    const res = await fetch(API);
    const products = await res.json();

    lista.innerHTML = "";

    products.forEach(p => {

        const li = document.createElement("li");

        li.innerText = `${p.nombre} - Cantidad: ${p.cantidad} - Vence: ${p.vencimiento}`;

        lista.appendChild(li);
    });
}

cargarProductos();