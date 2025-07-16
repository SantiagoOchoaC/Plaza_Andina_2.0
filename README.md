# Plaza_Andina_2.0

Sistema para gestionar mesas y pedidos en restaurantes. Administra el ingreso de clientes, asignación de mesas y meseros, pedidos por secciones (barra, coctel, cocina), notificaciones de productos listos, facturación y liberación de mesas para nuevos clientes.

---

## Índice
- [Características principales](#características-principales)
- [Instalación](#instalación)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Tecnologías utilizadas](#tecnologías-utilizadas)
- [Uso](#uso)
---

## Características principales

- Gestión y registro de clientes.
- Asignación y liberación de mesas.
- Administración de meseros.
- Toma y envío de pedidos por secciones (barra, coctel, cocina).
- Notificaciones automáticas de productos listos.
- Facturación de pedidos y cierre de cuentas.
- Reportes básicos de ocupación y ventas.

---

## Instalación

1. Clona el repositorio:
   ```bash
   git clone https://github.com/SantiagoOchoaC/Plaza_Andina_2.0.git
   ```
2. Configura un servidor web compatible con PHP (por ejemplo, XAMPP, WAMP, o LAMP).
3. Importa la base de datos desde el archivo proporcionado en la carpeta `/database` (ajusta la ruta si existe).
4. Configura los datos de conexión a la base de datos en el archivo correspondiente (por ejemplo, `config.php`).
5. Accede al sistema desde tu navegador usando la URL local.

---

## Estructura del proyecto

- `/src` - Código fuente principal.
- `/database` - Scripts y backups de la base de datos.
- `/public` - Archivos públicos y recursos estáticos (CSS, imágenes).
- `/docs` - Documentación adicional (si existe).

---

## Tecnologías utilizadas

- **PHP** (backend)
- **HTML y CSS** (frontend)
- **MySQL** (base de datos)
- **Javascript** (si aplica)

---

## Uso

1. Ingresa al sistema como administrador, mesero o cajero según los permisos configurados.
2. Registra el ingreso de clientes y asigna una mesa.
3. Realiza pedidos desde la mesa correspondiente y selecciona la sección adecuada.
4. Recibe notificaciones cuando los productos estén listos.
5. Realiza la facturación y libera la mesa para nuevos clientes.

---
