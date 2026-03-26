let products = [];

const getProducts = (req, res) => {
    res.json(products);
};

const addProduct = (req, res) => {

    const { nombre, cantidad, vencimiento } = req.body;

    const product = {
        id: Date.now(),
        nombre,
        cantidad,
        vencimiento
    };

    products.push(product);

    res.json(product);
};

module.exports = {
    getProducts,
    addProduct
};