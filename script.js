// Asegurarse de que solo el login esté visible al cargar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('login').style.display = 'flex';
    document.getElementById('register').style.display = 'none';
    document.getElementById('mainApp').style.display = 'none';

    // Vincular eventos solo si los elementos existen
    const backupBtn = document.getElementById('backupBtn');
    if (backupBtn) {
        backupBtn.addEventListener('click', function() {
            const currentDate = new Date().toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            if (confirm('¿Desea realizar una copia de seguridad de la base de datos?')) {
                alert(`Copia de seguridad realizada exitosamente el ${currentDate}.`);
            }
        });
    } else {
        console.warn('El botón #backupBtn no se encontró en el DOM.');
    }
});

// Simulación de usuarios registrados
let users = [
    { username: 'José', password: '12345678', role: 'Administrador' }
];

// Simulación de lista de productos
let products = [
    { code: '001', name: 'Paracetamol 500mg', category: 'Medicamentos', stock: 50, price: 500.00 },
    { code: '002', name: 'Crema Hidratante', category: 'Cosméticos', stock: 20, price: 500.00 }
];

// Simulación de lista de proveedores
let providers = [
    { id: '001', name: 'Distribuidora Salud', contact: '300-123-4567' },
    { id: '002', name: 'Cosmética SAS', contact: '310-987-6543' }
];

// Simulación de lista de formas de pago
let payments = [];

// Simulación de lista de clientes (Nueva)
let clients = [
    { name: 'Juan Pérez', address: 'Calle 123 #45-67', id: '12345678-9', phone: '300-123-4567' }
];

// Simulación del número de inicio de factura
let invoiceStartNumber = null;

// Login - Corrección: Mensaje de error claro
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const user = users.find(u => u.username === username && u.password === password);
    const existingError = document.getElementById('loginError');
    if (existingError) existingError.remove();

    if (user) {
        document.getElementById('login').style.display = 'none';
        document.getElementById('register').style.display = 'none';
        document.getElementById('mainApp').style.display = 'flex';
        loadMenu(user.role);
    } else {
        const errorMsg = document.createElement('p');
        errorMsg.id = 'loginError';
        errorMsg.textContent = 'Usuario o contraseña incorrectos';
        errorMsg.style.color = '#dc3545';
        errorMsg.setAttribute('role', 'alert');
        document.getElementById('loginForm').appendChild(errorMsg);
        setTimeout(() => errorMsg.remove(), 3000);
    }
});

// Mostrar formulario de registro
document.getElementById('showRegister').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('login').style.display = 'none';
    document.getElementById('register').style.display = 'flex';
});

// Registro de usuario
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const role = document.getElementById('regRole').value;
    users.push({ username: name, password: password, role: role });
    alert('Usuario registrado exitosamente');
    document.getElementById('register').style.display = 'none';
    document.getElementById('login').style.display = 'flex';
    document.getElementById('registerForm').reset();
});

// Volver al login desde registro
document.getElementById('backToLogin').addEventListener('click', function() {
    document.getElementById('register').style.display = 'none';
    document.getElementById('login').style.display = 'flex';
});

// Cerrar sesión
document.getElementById('logout')?.addEventListener('click', function() {
    document.getElementById('mainApp').style.display = 'none';
    document.getElementById('login').style.display = 'flex';
    document.getElementById('loginForm').reset();
});

// Cargar menú según rol
function loadMenu(role) {
    const navMenu = document.getElementById('navMenu');
    navMenu.innerHTML = '';
    const commonModules = `
        <li class="nav-item"><a class="nav-link active" href="#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#productos">Productos</a></li>
        <li class="nav-item"><a class="nav-link" href="#ventas">Ventas</a></li>
    `;
    const adminModules = `
        ${commonModules}
        <li class="nav-item"><a class="nav-link" href="#inventario">Inventario</a></li>
        <li class="nav-item"><a class="nav-link" href="#contabilidad">Contabilidad</a></li>
        <li class="nav-item"><a class="nav-link" href="#proveedores">Proveedores</a></li>
        <li class="nav-item"><a class="nav-link" href="#compras">Compras</a></li>
        <li class="nav-item"><a class="nav-link" href="#configuracion">Configuración</a></li>
        <li class="nav-item"><a class="nav-link" href="#formasDePago">Formas de Pago</a></li>
        <li class="nav-item"><a class="nav-link" href="#clientes">Clientes</a></li>
        <li class="nav-item"><a class="nav-link" href="#copiaSeguridad">Copia de Seguridad</a></li>
        <li class="nav-item mt-5"><button class="btn btn-logout w-100" id="logout">Cerrar Sesión</button></li>
    `;
    const vendorModules = `
        ${commonModules}
        <li class="nav-item mt-5"><button class="btn btn-logout w-100" id="logout">Cerrar Sesión</button></li>
    `;
    navMenu.innerHTML = role === 'Administrador' ? adminModules : vendorModules;

    // Asegurarse de que todos los enlaces del menú funcionen
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSection = document.querySelector(this.getAttribute('href'));
            if (targetSection) {
                document.querySelectorAll('.content > div').forEach(div => div.classList.add('d-none'));
                targetSection.classList.remove('d-none');
                document.querySelectorAll('.nav-link').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
            } else {
                console.error(`No se encontró la sección con ID: ${this.getAttribute('href')}`);
            }
        });
    });

    const logoutBtn = document.getElementById('logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            document.getElementById('mainApp').style.display = 'none';
            document.getElementById('login').style.display = 'flex';
            document.getElementById('loginForm').reset();
        });
    }
}

document.getElementById('categoryFilter')?.addEventListener('change', function() {
    const selectedCategory = this.value;
    const rows = document.querySelectorAll('#inventoryTable tr');
    rows.forEach(row => {
        const category = row.cells[2].textContent;
        if (selectedCategory === 'Todas las Categorías' || category === selectedCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Eliminar productos
document.querySelectorAll('#productos .btn-danger')?.forEach(button => {
    button.addEventListener('click', function() {
        if (confirm('¿Estás seguro de eliminar este producto?')) {
            this.parentElement.parentElement.remove();
            const code = this.closest('tr').cells[0].textContent;
            products = products.filter(p => p.code !== code);
        }
    });
});

// Eliminar elementos del inventario
document.querySelectorAll('#inventario .btn-danger')?.forEach(button => {
    button.addEventListener('click', function() {
        if (confirm('¿Estás seguro de eliminar este producto del inventario?')) {
            this.parentElement.parentElement.remove();
        }
    });
});

// Eliminar proveedores
document.querySelectorAll('#proveedores .btn-danger')?.forEach(button => {
    button.addEventListener('click', function() {
        if (confirm('¿Estás seguro de eliminar este proveedor?')) {
            this.parentElement.parentElement.remove();
            const id = this.closest('tr').cells[0].textContent;
            providers = providers.filter(p => p.id !== id);
        }
    });
});

document.getElementById('addProviderBtn').addEventListener('click', function() {
    document.getElementById('addProviderForm').style.display = 'block';
});

document.getElementById('providerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('providerId').value;
    const name = document.getElementById('providerName').value;
    const contact = document.getElementById('providerContact').value;
    providers.push({ id, name, contact });
    const tableBody = document.querySelector('#proveedores tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${id}</td>
        <td>${name}</td>
        <td>${contact}</td>
        <td>
            <button class="btn btn-warning btn-sm me-1">Editar</button>
            <button class="btn btn-danger btn-sm">Eliminar</button>
        </td>
    `;
    tableBody.appendChild(newRow);
    document.querySelectorAll('#proveedores .btn-danger').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('¿Estás seguro de eliminar este proveedor?')) {
                this.parentElement.parentElement.remove();
                const id = this.closest('tr').cells[0].textContent;
                providers = providers.filter(p => p.id !== id);
            }
        });
    });
    document.getElementById('providerForm').reset();
    document.getElementById('addProviderForm').style.display = 'none';
});

document.getElementById('cancelAddProvider').addEventListener('click', function() {
    document.getElementById('addProviderForm').style.display = 'none';
    document.getElementById('providerForm').reset();
});

document.getElementById('addProductBtn').addEventListener('click', function() {
    document.getElementById('addProductForm').style.display = 'block';
});

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const code = document.getElementById('productCode').value;
    const name = document.getElementById('productName').value;
    const category = document.getElementById('productCategory').value;
    const stock = document.getElementById('productStock').value;
    const price = parseFloat(document.getElementById('productPrice').value) || 0.00;
    products.push({ code, name, category, stock, price });
    const tableBody = document.querySelector('#productos tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${code}</td>
        <td>${name}</td>
        <td>${category}</td>
        <td>${stock}</td>
        <td>${price.toFixed(2)}</td>
        <td>
            <button class="btn btn-warning btn-sm me-1">Editar</button>
            <button class="btn btn-danger btn-sm">Eliminar</button>
        </td>
    `;
    tableBody.appendChild(newRow);
    document.querySelectorAll('#productos .btn-danger').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('¿Estás seguro de eliminar este producto?')) {
                this.parentElement.parentElement.remove();
                const code = this.closest('tr').cells[0].textContent;
                products = products.filter(p => p.code !== code);
            }
        });
    });
    document.getElementById('productForm').reset();
    document.getElementById('addProductForm').style.display = 'none';
});

document.getElementById('cancelAddProduct').addEventListener('click', function() {
    document.getElementById('addProductForm').style.display = 'none';
    document.getElementById('productForm').reset();
});

document.getElementById('addPaymentBtn').addEventListener('click', function() {
    document.getElementById('addPaymentForm').style.display = 'block';
    document.getElementById('paymentForm').reset();
    document.getElementById('bankFields').style.display = 'none';
});

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const method = document.getElementById('paymentMethod').value;
    let details = '';
    if (method === 'cuentaAhorros') {
        const bankName = document.getElementById('bankName').value || 'No especificado';
        const bankAccount = document.getElementById('bankAccount').value || 'No especificado';
        details = `${bankName} - ${bankAccount}`;
    } else if (method === 'efectivo') {
        details = 'Efectivo';
    }
    payments.push({ method, details });
    updatePaymentTable();
    document.getElementById('bankFields').style.display = 'none';
    document.getElementById('paymentForm').reset();
    document.getElementById('addPaymentForm').style.display = 'none';
});

document.getElementById('cancelAddPayment').addEventListener('click', function() {
    document.getElementById('addPaymentForm').style.display = 'none';
    document.getElementById('paymentForm').reset();
    document.getElementById('bankFields').style.display = 'none';
});

document.getElementById('paymentMethod').addEventListener('change', function() {
    const bankFields = document.getElementById('bankFields');
    if (this.value === 'cuentaAhorros') {
        bankFields.style.display = 'block';
    } else {
        bankFields.style.display = 'none';
    }
});

// Función para actualizar la tabla de formas de pago
function updatePaymentTable() {
    const tableBody = document.getElementById('paymentTable').querySelector('tbody');
    tableBody.innerHTML = '';
    payments.forEach((payment, index) => {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${payment.method.charAt(0).toUpperCase() + payment.method.slice(1)}</td>
            <td>${payment.details}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1 edit-payment" data-index="${index}">Editar</button>
                <button class="btn btn-danger btn-sm delete-payment" data-index="${index}">Eliminar</button>
            </td>
        `;
        tableBody.appendChild(newRow);
    });

    // Eventos para eliminar
    document.querySelectorAll('.delete-payment').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('¿Estás seguro de eliminar esta forma de pago?')) {
                const index = this.getAttribute('data-index');
                payments.splice(index, 1);
                updatePaymentTable();
            }
        });
    });

    // Eventos para editar
    document.querySelectorAll('.edit-payment').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const payment = payments[index];
            document.getElementById('paymentMethod').value = payment.method;
            if (payment.method === 'cuentaAhorros') {
                document.getElementById('bankFields').style.display = 'block';
                const [bankName, bankAccount] = payment.details.split(' - ');
                document.getElementById('bankName').value = bankName;
                document.getElementById('bankAccount').value = bankAccount;
            } else {
                document.getElementById('bankFields').style.display = 'none';
            }
            document.getElementById('addPaymentForm').style.display = 'block';
            payments.splice(index, 1); // Elimina el registro original para reemplazarlo al guardar
        });
    });
}

// Gestión de Clientes (Nueva)
document.getElementById('addClientBtn').addEventListener('click', function() {
    document.getElementById('addClientForm').style.display = 'block';
    document.getElementById('clientForm').reset();
});

document.getElementById('clientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const name = document.getElementById('clientName').value;
    const address = document.getElementById('clientAddress').value;
    const id = document.getElementById('clientId').value;
    const phone = document.getElementById('clientPhone').value;
    clients.push({ name, address, id, phone });
    updateClientTable();
    document.getElementById('clientForm').reset();
    document.getElementById('addClientForm').style.display = 'none';
});

document.getElementById('cancelAddClient').addEventListener('click', function() {
    document.getElementById('addClientForm').style.display = 'none';
    document.getElementById('clientForm').reset();
});

function updateClientTable() {
    const tableBody = document.getElementById('clientTable').querySelector('tbody');
    tableBody.innerHTML = '';
    clients.forEach((client, index) => {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>${client.name}</td>
            <td>${client.address}</td>
            <td>${client.id}</td>
            <td>${client.phone}</td>
            <td>
                <button class="btn btn-warning btn-sm me-1 edit-client" data-index="${index}">Editar</button>
                <button class="btn btn-danger btn-sm delete-client" data-index="${index}">Eliminar</button>
            </td>
        `;
        tableBody.appendChild(newRow);
    });

    // Eventos para eliminar clientes
    document.querySelectorAll('.delete-client').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('¿Estás seguro de eliminar este cliente?')) {
                const index = this.getAttribute('data-index');
                clients.splice(index, 1);
                updateClientTable();
            }
        });
    });

    // Eventos para editar clientes
    document.querySelectorAll('.edit-client').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const client = clients[index];
            document.getElementById('clientName').value = client.name;
            document.getElementById('clientAddress').value = client.address;
            document.getElementById('clientId').value = client.id;
            document.getElementById('clientPhone').value = client.phone;
            document.getElementById('addClientForm').style.display = 'block';
            clients.splice(index, 1); // Elimina el registro original para reemplazarlo al guardar
        });
    });
}

document.getElementById('invoiceConfigForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const number = document.getElementById('invoiceStartNumber').value;
    if (number && !isNaN(number)) {
        invoiceStartNumber = parseInt(number);
        alert(`Número de inicio de factura configurado como: ${invoiceStartNumber}`);
        document.getElementById('invoiceConfigForm').reset();
    } else {
        alert('Por favor, ingrese un número válido para el inicio de factura.');
    }
});

document.getElementById('exportDashboardExcel').addEventListener('click', function() {
    const data = [
        ['Productos Más Vendidos', '', '', '', '', 'Ventas por Mes', '', '', 'Acumulado', '', 'Fechas de Vencimiento de Medicamentos'],
        ['Producto', 'Cantidad Vendida', '', '', '', 'Mes', 'Total ($)', '', 'Total Acumulado ($)', '', 'Producto', 'Fecha de Vencimiento', 'Estado']
    ];
    document.querySelectorAll('#inicio .table-sm').forEach(table => {
        const rows = table.querySelectorAll('tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td, th');
            const rowData = [];
            cells.forEach(cell => rowData.push(cell.textContent));
            data.push(rowData);
        });
    });
    exportToCsv('Dashboard_Report.csv', data);
});

document.getElementById('exportImpuestosExcel').addEventListener('click', function() {
    const data = [['Fecha', 'Concepto', 'Valor']];
    document.querySelectorAll('#impuestos table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(cell => rowData.push(cell.textContent));
        data.push(rowData);
    });
    exportToCsv('Impuestos_DIAN_Report.csv', data);
});

document.getElementById('exportIngresosExcel').addEventListener('click', function() {
    const data = [['Fecha', 'Descripción', 'Valor']];
    document.querySelectorAll('#ingresos table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(cell => rowData.push(cell.textContent));
        data.push(rowData);
    });
    exportToCsv('Ingresos_Report.csv', data);
});

document.getElementById('exportEgresosExcel').addEventListener('click', function() {
    const data = [['Fecha', 'Descripción', 'Valor']];
    document.querySelectorAll('#egresos table tbody tr').forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [];
        cells.forEach(cell => rowData.push(cell.textContent));
        data.push(rowData);
    });
    exportToCsv('Egresos_Report.csv', data);
});

function exportToCsv(filename, data) {
    const csv = [];
    data.forEach(row => {
        csv.push(row.join(','));
    });
    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}