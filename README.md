# Proyecto Backend Agro Tech Connect
![Logo](/public/assets/logo_agrotechconnectlight.png)

## Descripción
Este es un backend desarrollado en Laravel para la plataforma **Agro Tech Connect**, utilizando diversas tecnologías y herramientas modernas como Docker, Firebase para autenticación, y Redis para optimización del sistema.

## Tecnologías utilizadas
- Laravel
- Laravel Sail (para gestión de Docker)
- MySQL (en Docker)
- Laravel Scramble (documentación)
- Firebase (Autenticación con Google y Facebook)
- Laravel Sanctum (Autenticación de API)
- Laravel Permission (Gestión de roles y permisos)
- Redis (Optimización de caché y colas de trabajo)
- Cloudinary (Almacenamiento de imágenes)
- MailPit (Gestor de correos en Docker)

## Instalación y configuración
Siga los siguientes pasos para configurar el proyecto en su entorno local:

```bash
# Clonar el repositorio
git clone https://github.com/JeanDev-10/API-Agro-Tech-Connect.git
cd API-AgroTechConnect

# Instalar dependencias de Laravel
composer install

# Copiar el archivo de configuración y luego reemplazar las variables de entorno correspondientes
tcp .env.example .env

# Generar la clave de la aplicación
php artisan key:generate

# Levantar los contenedores con Docker
./vendor/bin/sail up -d

# Ejecutar migraciones y seeders
./vendor/bin/sail artisan migrate --seed

# Iniciar el servidor
./vendor/bin/sail artisan serve

# Correr test
./vendor/bin/sail artisan test

```
## Módulos del sistema
El backend cuenta con los siguientes módulos:
- **Autenticación**
- **Publicaciones**
- **Comentarios y Valoraciones**
- **Usuarios**
- **Administración**

## Principales Reglas de Negocio

### Módulo de Autenticación
#### Registro de Usuarios
- **Correo**
  - Formato de correo válido
  - Correo único
- **Nombres y Apellidos**
  - Mínimo 3 y máximo 20 caracteres
- **Nombre de Usuario**
  - Usuario único
  - Longitud de 3 a 10 caracteres
- **Contraseña**
  - Mínimo 8 y máximo 15 caracteres
  - Incluir al menos un carácter especial (@, &, $, %)
  - Incluir al menos una letra mayúscula
  - Incluir al menos un número
- **Confirmar Contraseña**
  - Debe cumplir los mismos requisitos de la contraseña
  - Debe coincidir con la contraseña

#### Recuperación de Contraseña
- **Nueva Contraseña**
  - Mínimo 8 y máximo 15 caracteres
  - Incluir al menos un carácter especial (@, &, $, %)
  - Incluir al menos una letra mayúscula
  - Incluir al menos un número
  - Debe ser distinta a las usadas previamente
- **Confirmar Nueva Contraseña**
  - Debe cumplir los mismos requisitos de la nueva contraseña
  - Debe coincidir con la nueva contraseña

### Módulo de Usuario
- **Personalizar mi perfil**
  - Descripción de hasta 100 caracteres
  - Máximo 3 enlaces a redes sociales opcionales
- **Cambio de contraseña**
  - Validar la contraseña actual
  - Nueva contraseña debe cumplir los mismos requisitos de seguridad
  - No debe ser igual a contraseñas anteriores
- **Subir un avatar**
  - Formatos válidos: JPG, PNG, JPEG
  - Peso máximo: 3 MB
  - Solo se permite una imagen
- **Seguir a otro usuario**
  - No es necesario ser seguido para seguir a otro usuario
  - No se puede seguir más de una vez al mismo usuario
  - No se puede seguir a sí mismo ni al administrador
- **Dejar de seguir a un usuario**
  - Solo se puede dejar de seguir si previamente se estaba siguiendo
  - No se puede dejar de seguir a sí mismo ni al administrador
- **Dar de baja mi cuenta**
  - Requiere confirmación de contraseña
  - No se pueden eliminar cuentas de otros usuarios

### Módulo de Publicaciones
- **Eliminar publicaciones**
  - No se pueden eliminar publicaciones de otros
  - La publicación debe existir para eliminarla
  - No se puede eliminar la misma publicación dos veces
- **Editar publicaciones**
  - Solo se pueden editar publicaciones propias
  - Máximo 250 caracteres
  - Formatos de imagen permitidos: JPG, PNG, JPEG
  - Peso máximo: 3 MB
  - Máximo 10 imágenes
- **Crear publicaciones**
  - Máximo 250 caracteres
  - Formatos de imagen permitidos: JPG, PNG, JPEG
  - Peso máximo: 3 MB
  - Máximo 10 imágenes

### Módulo de Comentar y Valorar
- **Denunciar publicación**
  - Un usuario solo puede denunciar una publicación hasta 5 veces
- **Comentar la publicación**
  - Máximo 100 caracteres
  - Formatos de imagen permitidos: JPG, PNG, JPEG
  - Peso máximo: 3 MB
  - Máximo 5 imágenes
- **Editar mi comentario**
  - Solo se pueden editar comentarios propios
  - Máximo 100 caracteres
  - Formatos de imagen permitidos: JPG, PNG, JPEG
  - Peso máximo: 3 MB
  - Máximo 5 imágenes
- **Denunciar un comentario**
  - Un usuario solo puede denunciar un comentario hasta 5 veces

### Módulo de Administrador
- **Eliminar cuenta**
  - Puede eliminar cuentas de usuarios
  - No puede eliminar su propia cuenta como administrador

## Rangos de usuario
El sistema de rangos se basa en la cantidad de "Positivos" obtenidos en comentarios:

| Rango            | Requisito                     | Descripción |
|-----------------|-----------------------------|-------------|
| Iniciado        | 0 - 49 Positivos             | ¡Bienvenido a la comunidad! Estás comenzando tu viaje como iniciado. |
| Novato         | 50 - 199 Positivos           | Has recibido tus primeros "Positivos". ¡Sigue participando! |
| Aprendiz       | 200 - 499 Positivos          | Tus comentarios están siendo valorados. ¡Vas por buen camino! |
| Contribuyente  | 500 - 999 Positivos          | Eres un miembro activo y valorado en la comunidad. |
| Veterano       | 1,000 - 2,499 Positivos      | Tus aportes son reconocidos y respetados por la comunidad. |
| Experto        | 2,500 - 4,999 Positivos      | Eres una voz autorizada en la comunidad. ¡Felicidades! |
| Maestro        | 5,000 - 9,999 Positivos      | Tus comentarios son referencia para otros usuarios. |
| Gran Maestro   | 10,000 - 24,999 Positivos    | Eres un pilar de la comunidad. ¡Tu experiencia es invaluable! |
| Leyenda        | 25,000+ Positivos            | Has alcanzado el máximo reconocimiento. ¡Eres una leyenda! |

![Diseño de insignias](/public/assets/tableiconscolors.png)


## Diagramas
A continuación, se agregarán los diagramas correspondientes al sistema:

### Diagrama de Casos de Uso
![Diagrama de casos de uso](/public/assets/Agro%20Tech%20Connect%20-%20Casos%20de%20uso-Módulos.png)

### Diagrama de la Base de Datos
![Diagrama de la Base de Datos](/public/assets/Agro%20Tech%20Connect%20-%20Base%20de%20datos%20con%20notas.png)


## EndPoints disponibles con sus diferentes casos de uso y pruebas
### **URL para consultas de API (excepto Docs)**: `http://localhost/api/v1/`
- **Acceder a documentación**: `GET /docs/api`
- **Registrarse**: `POST /auth/register`
    - Registro exitoso
    - Error de validaciones
    - Email usado
    - Username usado
- **Inicio de sesión correo y contraseña**: `POST /auth/login`
    - Login exitoso
    - Error de validaciones
    - Credenciales incorrectas
    - Usuario no existe
- **Cerrar sesión**: `POST /auth/logout`
    - Logout exitoso
    - No autenticado
- **Confirmación de correo**: `POST /email/verify/{id}/{hash}`
- **Envio de correo**: `POST /email/verify/send`
    - Verificado exitoso
    - Token modificado
    - Cuenta ya verificada
    - Volver a enviar correo
    - Solo poder enviar 2 correos por cada 1hr con 1hr de validez al enlace
- **Envio correo reestablecer contraseña**: `POST /password/forgot`
- **Reestablecer contraseña**: `POST /password/reset`
    - Pedir enlace sin especificar correo
    - Pedir enlace exitoso
    - Pedir enlace con correo inexistente 
    - Alterar token o email al reestablecer contraseña
    - Errores de validación al reestablecer contrsaeña
    - Contraseña usada anteriormente
    - Error de haber usado más de 2 envios de correos en 1hr
- **Login Facebook**: `POST /auth/login/facebook`
- **Login Google**: `POST /auth/login/google`
    - Error al no mandar el token
    - Error al mandar token alterado
    - Registro exitoso con token correcto
- **Cambiar contraseña**: `PUT /me/password`
    - Cambio exitoso
    - Contraseña actual incorrecta
    - Errores de validación
    - Error contraseña antes fue usada
    - Error no estás autorizado (para usuarios de Google y Facebook)
- **Eliminar cuenta local**: `PUT /me/`
- **Eliminar cuenta social**: `PUT /me/social`
    - Eliminar cuenta exitoso local
    - Eliminar cuenta exitoso social
    - Eliminar cuenta error contraseña incorrecta
    - Eliminar cuenta error validaciones
    - Error eliminar cuenta local con endpoint social 
    - Error eliminar cuenta social con endpoint local 



## Autores
- [Jean Pierre Rodríguez Zambrano](https://github.com/JeanDev-10)
- [Jahir Alexander Celorio Malavé](https://github.com/JCelorioDev)

---

## Licencia

[MIT](https://choosealicense.com/licenses/mit/)

---
Este documento se actualizará conforme avance el desarrollo del proyecto.
