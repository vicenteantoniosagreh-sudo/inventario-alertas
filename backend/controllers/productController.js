let products = [];

const getProducts = (req, res) => {
    res.json(products);
};

const addProduct = (req, res) => {

    const { nombre, cantidad, vencimiento } = req.body;

    if (!nombre || !cantidad || !vencimiento) {
        return res.status(400).json({ error: "Todos los campos son obligatorios" });
    }

    const product = {
        id: Date.now(),
        nombre: nombre.trim(),
        cantidad: Number(cantidad),
        vencimiento: vencimiento
    };

    products.push(product);

    res.json(product);
};

const deleteProduct = (req, res) => {
    const id = Number(req.params.id);

    const prevLength = products.length;
    products = products.filter((p) => p.id !== id);

    if (products.length === prevLength) {
        return res.status(404).json({ error: "Producto no encontrado" });
    }

    res.json({ success: true });
};

module.exports = {
    getProducts,
    addProduct,
    deleteProduct
};