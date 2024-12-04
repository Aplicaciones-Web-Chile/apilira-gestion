# API Lira Gestión

API REST desarrollada en PHP para la gestión de usuarios y dashboard.

## Características

- Autenticación mediante API keys
- Manejo de usuarios (registro y login)
- Dashboard con datos de gestión
- Soporte para múltiples formatos de respuesta (JSON, XML)
- Manejo de errores personalizado
- Conexión a base de datos Oracle

## Requisitos

- PHP 7.0 o superior
- PDO Oracle
- Base de datos Oracle

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/TU_USUARIO/apilira-gestion.git
```

2. Configurar la base de datos:
- Copiar `config.example.php` a `config.php`
- Editar las credenciales de la base de datos en `config.php`

3. Configurar el servidor web:
- Asegurarse que el módulo de Oracle para PHP esté instalado
- Configurar el virtual host apuntando a la carpeta del proyecto

## Estructura del Proyecto

```
apilira-gestion/
├── controladores/     # Controladores de la API
├── datos/            # Capa de acceso a datos
├── utilidades/       # Clases y funciones auxiliares
├── vistas/           # Formateadores de respuesta
└── index.php         # Punto de entrada de la API
```

## Uso

### Endpoints Disponibles

- `POST /usuarios/registro` - Registro de nuevos usuarios
- `POST /usuarios/login` - Autenticación de usuarios
- `GET /dashboard` - Obtención de datos del dashboard

### Ejemplos de Uso

```bash
# Registro de usuario
curl -X POST http://api.ejemplo.com/usuarios/registro \
  -H "Content-Type: application/json" \
  -d '{"id_usuario":"usuario1","contrasena":"123456","nombre":"Usuario Test"}'

# Login
curl -X POST http://api.ejemplo.com/usuarios/login \
  -H "Content-Type: application/json" \
  -d '{"id_usuario":"usuario1","contrasena":"123456"}'
```

## Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.
