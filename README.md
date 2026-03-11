# Rutiar

Rutiar es un sistema de gestión de rutas de transporte diseñado para empresas (en desarrollo). Permite modelar y optimizar rutas, realizar seguimiento en tiempo real, gestionar conductores y vehículos, y obtener analíticas de desempeño. Está construido principalmente con Laravel (PHP) en el backend, MySQL como base de datos, y herramientas modernas para el frontend y el empaquetado de assets (Vite). El proyecto expone una API RESTful para integraciones con clientes móviles o sistemas externos.

Características principales

- Optimización y planificación de rutas.
- Seguimiento en tiempo real (telemetría/location updates).
- Gestión de conductores, vehículos y asignaciones.
- Analítica e informes de rendimiento.
- API RESTful para integraciones.
- Tests automatizados con PHPUnit y estructura preparada para desarrollo local con Docker.

Tecnologías

- Backend: Laravel (PHP)
- Base de datos: MySQL
- Frontend/build: Vite, Node.js
- Contenedores: Docker / compose.yaml incluido
- Tests: PHPUnit

Instalación rápida (local)

1. Clonar el repositorio:

   git clone https://github.com/viteant/rutiar_core.git
   cd rutiar_core

2. Copiar el archivo de entorno y ajustar variables:

   cp .env.example .env
   # editar .env según su entorno (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, etc.)

3. Instalar dependencias PHP y Node:

   composer install
   npm install

4. Ejecutar migraciones y seeders (si aplica):

   php artisan migrate --seed

5. Ejecutar servidores locales:

   php artisan serve
   npm run dev

Opcional: arrancar con Docker

Si prefiere usar contenedores, revise el archivo compose.yaml incluido y ejecute:

   docker compose up --build

Ejecutar tests

   ./vendor/bin/phpunit

Estructura del repositorio

- app/: código fuente Laravel (controladores, modelos, servicios)
- config/: archivos de configuración
- database/: migraciones y seeders
- routes/: definiciones de rutas API/web
- resources/: vistas y assets
- tests/: pruebas automatizadas
- compose.yaml: definición para Docker

Contribuciones

Las contribuciones son bienvenidas. Abra issues para reportar problemas o proponer mejoras y envíe pull requests con cambios propuestos.

Licencia

Revisa el archivo LICENSE en el repositorio para detalles de licencia (si aplica).

---

(Actualización automática del README solicitada por el mantenedor para añadir una descripción más completa en español.)