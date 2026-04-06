const authForm = document.getElementById('authForm');
const toggleAuthBtn = document.getElementById('toggleAuthBtn');
const authMensaje = document.getElementById('authMensaje');
const authTitle = document.getElementById('authTitle');

let currentAuthAction = 'login';

// Si ya hay token, ir directo al inventario
if (localStorage.getItem('token')) {
    window.location.href = 'index.php';
}

toggleAuthBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (currentAuthAction === 'login') {
        currentAuthAction = 'register';
        authTitle.textContent = 'Crear Cuenta';
        document.getElementById('authSubmit').textContent = 'Registrar';
        toggleAuthBtn.textContent = '¿Ya tienes cuenta? Inicia Sesión';
    } else {
        currentAuthAction = 'login';
        authTitle.textContent = 'Iniciar Sesión';
        document.getElementById('authSubmit').textContent = 'Ingresar';
        toggleAuthBtn.textContent = '¿No tienes cuenta? Regístrate';
    }
    authMensaje.textContent = '';
    authMensaje.className = "alert";
});

authForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const usuario = document.getElementById('authUsuario').value;
    const password = document.getElementById('authPassword').value;

    try {
        const res = await fetch(`../backend/auth.php?action=${currentAuthAction}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario, password })
        });

        const data = await res.json();

        if (!res.ok) throw new Error(data.error || "Error al autenticar");

        if (currentAuthAction === 'register') {
            // Registro exitoso: login automático y redirigir al inventario
            authMensaje.textContent = data.message + ' Iniciando sesión...';
            authMensaje.className = "alert success";
            const loginRes = await fetch('../backend/auth.php?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario, password })
            });
            const loginData = await loginRes.json();
            if (loginRes.ok) {
                localStorage.setItem('token', loginData.token);
                localStorage.setItem('usuario', loginData.usuario);
                window.location.href = 'index.php';
            }
        } else {
            localStorage.setItem('token', data.token);
            localStorage.setItem('usuario', data.usuario);
            window.location.href = 'index.php';
        }
    } catch (error) {
        authMensaje.textContent = error.message;
        authMensaje.className = "alert error";
    }
});
