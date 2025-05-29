# Proyecto Backend Agro Tech Connect
![Logo](/public/assets/logonuevoagrotechconnect28_05_2025.png)

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
- **Comentarios**
- **Valoraciones**
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
- **Denunciar publicación**
  - Un usuario solo puede denunciar una publicación hasta 5 veces

### Módulo de Comentarios 

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
- **Denunciar una respuesta de comentario**
  - Un usuario solo puede denunciar una respuesta comentario hasta 5 veces

### Módulo de Valoraciones 

- **Reaccionar a comentario (positivo/negativo)**
  - Poder reaccionar solo una vez ya sea positivo o negativo
- **Reaccionar respuesta a comentario (positivo/negativo)**
  - Poder reaccionar solo una vez ya sea positivo o negativo
- **Reaccionar a publicación (positivo/negativo)**
  - Poder reaccionar solo una vez ya sea positivo o negativo

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
![Diagrama de casos de uso](/public/assets/Agro%20Tech%20Connect%20-%20Casos%20de%20uso-Módulos.jpeg)

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
    - Errores de validación al reestablecer contraseña
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
- **Crear/Actualizar mi información de usuario**: `POST /me/user-information`
    - Creado exitoso
    - Editado exitoso
    - Ver mi información exitoso
    - Ver mi información vacía sin registro
    - Errores de validación 
- **Crear/Actualizar mi información de usuario (avatar)**: `POST /me/avatar`
- **Eliminar mi avatar**: `DELETE /me/avatar`
    - Avatar creado exitoso
    - Avatar actualizado exitoso
    - Error no permitido para cuentas sociales (Google,Facebook)
    - Errores de validación 
    - Avatar eliminado exitoso
    - Avatar error no hay nada que eliminar
- **Seguir a un usuario**: `POST /users/follow`
    - Seguir exitoso
    - Error user_id modificado hash no valido
    - Error usuario a seguir no existe
    - Error ya estás siguiendo a ese usuario
    - Error intentaste seguirte a ti mismo
    - Error intentaste seguir al administrador
- **Dejar de seguir a un usuario**: `POST /users/unfollow`
    - Dejar de seguir exitoso
    - Error user_id modificado hash no valido
    - Error usuario a seguir no existe
    - Error no sigues a este usuario para dejarlo de seguir
- **Obtener notificaciones**: `GET /notifications`
- **Ver notificaciones sin leer**: `GET /notifications/unread`
- **Ver una notificación**: `GET /notifications/{id}`
- **Marcar como leida una notificación**: `PUT /notifications/{id}`
- **Marcar como leida todas las notificación**: `PUT /notifications/read-all`
- **Ver mi perfil**: `GET /me/profile`
- **Ver perfil de otro usuario**: `GET /user/profile`
    - Exitoso
    - No existe usuario
    - Id cifrado alterado
- **Ver mis seguidores**: `GET /me/followers`
- **Ver mis seguidos**: `GET /me/following`
- **Ver seguidores de un usuario**: `GET /users/{id}/followers`
    - Exitoso
    - No existe usuario
    - Id cifrado alterado
- **Ver seguidos de un usuario**: `GET /users/{id}/following`
    - Exitoso
    - No existe usuario
    - Id cifrado alterado
- **Ver todos los posts**: `GET /posts`
    - Exitoso
    - Exitoso con filtros
    - Filtros sin coicidencias  
- **Ver todos mis posts**: `GET me/posts`
    - Exitoso
    - Exitoso con filtros
    - Filtros sin coicidencias  
- **Ver posts de personas que sigo**: `GET me/following/posts`
    - Exitoso
    - Exitoso con filtros
    - Filtros sin coicidencias  
- **Ver posts de una persona en especifíco**: `GET me/following/posts`
    - Exitoso
    - Exitoso con filtros
    - Filtros sin coicidencias  
    - Id cifrado alterado
    - Usuario no existe  
- **Ver un post**: `GET /posts/{id}`
    - Exitoso
    - Id cifrado alterado
    - Post no encontrado 
- **Denunciar un post**: `POST /posts/{id}/complaint`
    - Exitoso
    - Id cifrado alterado
    - Post no encontrado 
    - Limite de 5 denuncias por post
    - Errores de validación
    - No autorizado
- **Crear un post**: `POST /posts`
    - Exitoso título y descripción
    - Exitoso título, descripción e imágenes
    - Error imágenes exceden tamaño (max 3MB)
    - Error imágenes formato incorrecto
    - Error al intentar subir más de 10 imágenes
    - Errores de validación
- **Editar un post**: `PUT /posts/{id}`
    - Caso exitoso editar publicación con titulo y descripción (sin imágenes originalmente)
    - Caso exitoso editar publicación con titulo y descripción agregando imágenes (sin imágenes originalmente)
    - Caso exitoso editar publicación con titulo, descripción e imágenes (reemplazando nuevas imágenes con las anteriores)
    - Caso erróneo editar publicación que no pertenece
    - Caso erróneo errores de validación
    - Caso erróneo publicación no encontrada
    - Caso erróneo ID alterado
- **Eliminar un post**: `DELETE /posts/{id}`
    - Caso exitoso eliminar publicación con titulo y descripción (sin imágenes originalmente)
    - Caso exitoso eliminar publicación con titulo y descripción e imágenes
    - Caso erróneo eliminar publicación que no pertenece
    - Caso erróneo eliminar publicación por el admin
    - Caso erróneo ID alterado
    - Caso erróneo publicación no encontrada
- **Eliminar todas las imágenes de un post**: `DELETE /posts/{id}/images`
    - Caso exitoso eliminar imágenes del post
    - Caso erróneo eliminar imágenes de post que no me pertenece
    - Caso erróneo post no encontrado
    - Caso erróneo id alterado
- **Eliminar una imagen de un post**: `DELETE /posts/{post}/images/{imagen}`
    - Caso exitoso eliminar una imagen
    - Caso erróneo no autorizado
    - Caso erróneo post no encontrado
    - Caso erróneo imagen no encontrada
    - Caso erróneo post id alterado
    - Caso erróneo imagen id alterado
- **Ver comentarios de una publicación**: `GET /posts/{post}/comments`
    - Caso exitoso
    - Caso exitoso posts sin comentarios
    - Caso erróneo post no encontrado
    - Caso erróneo post id alterado
- **Ver respuesta de comentarios de un comentario**: `GET /comments/{comment}/replaycomments`
    - Caso exitoso
    - Caso exitoso comentarios sin respuestas
    - Caso erróneo comentario no encontrado
    - Caso erróneo comentario id alterado
- **Ver un comentario**: `GET /comments/{comment}`
    - Caso exitoso
    - Caso erróneo no existe comentario
    - Caso erróneo id alterado
- **Denunciar un comentario**: `POST /comments/{comment}/complaint`
    - Caso exitoso
    - Caso no tienes permiso para acceder a este recurso (admin al querer realizar una denuncia)
    - Caso ID alterado cifrado
    - Caso limite de denuncias (5 max)
    - Caso Comment no existe
    - Caso Errores de validación
- **Denunciar una respuesta a comentario**: `POST /replaycomments/{comment}/complaint`
    - Caso exitoso
    - Caso no tienes permiso para acceder a este recurso (admin al querer realizar una denuncia)
    - Caso ID alterado cifrado
    - Caso limite de denuncias (5 max)
    - Caso Comment no existe
    - Caso Errores de validación
- **Comentar una publicación**: `POST /posts/{post}/comments`
    - Caso exitoso solo comentario
    - Caso exitoso comentario e imágenes
    - Caso erróneo imágenes formato incorrecto
    - Caso erróneo imágenes muy pesadas (mayor a 3mb)
    - Caso erróneo errores de validación 
    - Caso erróneo subir más de 5 imágenes
    - Caso erróneo post no encontrado
    - Caso erróneo id alterado
- **Editar comentario de una publicación**: `PUT /posts/{post}/comments/{comment}`
    - Caso exitoso editar publicación con comentario (sin imágenes)
    - Caso exitoso editar publicación con comentario y agregando imágenes (sin imágenes originalmente)
    - Caso exitoso editar publicación con titulo e imágenes (reemplazando nuevas imágenes con las anteriores)
    - Caso erróneo editar publicación que no pertenece
    - Caso erróneo errores de validación 
    - Caso erróneo imagen pesada
    - Caso erróneo más de 5 imágenes
    - Caso erróneo formato incorrecto de imagen
    - Caso erróneo comentario no encontrado
    - Caso erróneo id alterado cifrado
- **Responder un comentario**: `POST /posts/{post}/comments/{comment}/replaycomments`
    - Caso exitoso solo comentario
    - Caso exitoso comentario e imágenes
    - Caso erróneo imágenes formato incorrecto
    - Caso erróneo imágenes muy pesadas (mayor a 3mb)
    - Caso erróneo errores de validación 
    - Caso erróneo subir más de 5 imágenes
    - Caso erróneo comentario no encontrado
    - Caso erróneo id alterado
- **Editar respuesta a un comentario**: `PUT /posts/{post}/replaycomments/{replaycomment}`
    - Caso exitoso editar publicación con comentario (sin imágenes)
    - Caso exitoso editar publicación con comentario y agregando imágenes (sin imágenes originalmente)
    - Caso exitoso editar publicación con titulo e imágenes (reemplazando nuevas imágenes con las anteriores)
    - Caso erróneo editar publicación que no pertenece
    - Caso erróneo errores de validación
    - Caso erróneo imagen pesada
    - Caso erróneo más de 5 imágenes
    - Caso erróneo formato incorrecto de imagen
    - Caso erróneo respuesta a comentario no encontrado
    - Caso erróneo id alterado cifrado
- **Eliminar una imagen de un comentario**: `DELETE /comments/{comment}/images/{imagen}`
    - Caso exitoso eliminar una imagen
    - Caso erróneo no autorizado
    - Caso erróneo comentario no encontrado
    - Caso erróneo imagen no encontrada
    - Caso erróneo comentario id alterado
    - Caso erróneo imagen id alterado
- **Eliminar una imagen de una respuesta a comentario**: `DELETE /replaycomments/{comment}/images/{imagen}`
    - Caso exitoso eliminar una imagen
    - Caso erróneo no autorizado
    - Caso erróneo respuesta a comentario no encontrado
    - Caso erróneo imagen no encontrada
    - Caso erróneo respuesta a comentario id alterado
    - Caso erróneo imagen id alterado
- **Eliminar todas las imágenes de un comentario**: `DELETE /comments/{comment}/images/`
    - Caso exitoso eliminar imágenes
    - Caso erróneo eliminar imágenes que no me pertenece
    - Caso erróneo comentario no encontrado
    - Caso erróneo id alterado
- **Eliminar todas las imágenes de una respuesta a comentario**: `DELETE /replaycomments/{comment}/images/`
    - Caso exitoso eliminar imágenes
    - Caso erróneo eliminar imágenes que no me pertenece
    - Caso erróneo respuesta a comentario no encontrado
    - Caso erróneo id alterado
- **Obtener todas las reacciones incluyendo el usuario que la dió a una publicación**: `GET /posts/{post}/reactions/`
    - Caso exitoso 
    - Caso erróneo no encontrado
    - Caso erróneo id alterado
- **Obtener todas las reacciones incluyendo el usuario que la dió a un comentario**: `GET /comments/{comment}/reactions/`
    - Caso exitoso 
    - Caso erróneo no encontrado
    - Caso erróneo id alterado
- **Crear reacción a un comentario (Postivo/Negativo)**: `POST /comments/{comment}/reactions/`
    - Caso Exitoso (comentario sin reacción)
    - Caso Exitoso (comentario con reacción negativa anteriormente)
    - Caso Exitoso (comentario con reacción positiva anteriormente)
    - Caso Comentario no encontrado
    - Caso Id cifrado alterado
    - Caso ya has reaccionado positivo al comentario
    - Caso ya has reaccionado negativo al comentario
    - Caso error de validación
- **Eliminar reacción a un comentario**: `DELETE /comments/{comment}/reactions/`
    - Caso Exitoso 
    - Caso No encontrado
    - Caso Id cifrado alterado
    - Caso aún no has reaccionado
- **Crear reacción a un publicación (Postivo/Negativo)**: `POST /posts/{post}/reactions/`
    - Caso Exitoso (post sin reacción)
    - Caso Exitoso (post con reacción negativa anteriormente)
    - Caso Exitoso (post con reacción positiva anteriormente)
    - Caso post no encontrado
    - Caso Id cifrado alterado
    - Caso ya has reaccionado positivo al post
    - Caso ya has reaccionado negativo al post
    - Caso error de validación
- **Eliminar reacción a un publicación**: `DELETE /posts/{post}/reactions/`
    - Caso Exitoso 
    - Caso No encontrado
    - Caso Id cifrado alterado
    - Caso aún no has reaccionado
- **Crear reacción a una respuesta a comentario (Postivo/Negativo)**: `POST /replaycomments/{replayComment}/reactions/`
    - Caso Exitoso (respuesta a comentario sin reacción)
    - Caso Exitoso (respuesta a comentario con reacción negativa anteriormente)
    - Caso Exitoso (respuesta a comentario con reacción positiva anteriormente)
    - Caso respuesta a comentario no encontrado
    - Caso Id cifrado alterado
    - Caso ya has reaccionado positivo al respuesta a comentario
    - Caso ya has reaccionado negativo al respuesta a comentario
    - Caso error de validación
- **Eliminar reacción a una respuesta a comentario**: `DELETE /replaycomments/{replayComment}/reactions/`
    - Caso Exitoso 
    - Caso No encontrado
    - Caso Id cifrado alterado
    - Caso aún no has reaccionado
- **Eliminar una respuesta a comentario**: `DELETE /replaycomments/{replayComment}/`
    - Caso exitoso sin imágenes
    - Caso exitoso con imágenes
    - Caso erróneo no autorizado
    - Caso erróneo respuesta no encontrada
    - Caso erróneo respuesta id alterado cifrado
- **Eliminar un comentario**: `DELETE /comments/{comment}/`
    - Caso exitoso sin imágenes
    - Caso exitoso con imágenes
    - Caso erróneo no autorizado
    - Caso erróneo comentario no encontrada
    - Caso erróneo comentario id alterado cifrado
- **Eliminar una cuenta**: `DELETE /users/{user}/`
    - Caso exitoso
    - Caso erróneo no autorizado
    - Caso erróneo cuenta no encontrada
    - Caso erróneo id alterado cifrado
    - Caso erróneo eliminar mi propia cuenta como admin

## Autores
- [Jean Pierre Rodríguez Zambrano](https://github.com/JeanDev-10)
- [Jahir Alexander Celorio Malavé](https://github.com/JCelorioDev)

---

## Licencia

[MIT](https://choosealicense.com/licenses/mit/)

---
Este documento se actualizará conforme avance el desarrollo del proyecto.
