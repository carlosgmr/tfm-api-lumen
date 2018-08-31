# API REST con Lumen
Proyecto diseñado para mi TFM. Consiste en una API REST desarrollada en **PHP 7.2**
utilizando el framework **[Lumen 5.6](https://lumen.laravel.com/docs/5.6)**.

## Instalación y despliegue
Para instalar el proyecto hay que seguir los siguientes pasos:

1. Clonar o descargar proyecto desde GitHub: `git clone https://github.com/carlosgmr/tfm-api-lumen.git api-lumen`
2. Entrar en la carpeta del proyecto: `cd api-lumen`
3. Copiar el archivo `.env.example` y renombrarlo a `.env`
4. Abrir el archivo `.env` y completar las siguientes variables de configuración:

    - *APP_KEY*: cadena aleatoria que se utilizará para tareas de encriptación. Se recomienda que tenga una longitud mínima de 32 caracteres y que contega letras en mayúsculas y minúsculas y números.
    - *DB_HOST*: IP o nombre de dominio donde se encuentra la base de datos. **Importante** Si la base de datos se encuentra en el mismo equipo, no utilizar `localhost` o `127.0.0.1`, sino la IP que tiene el equipo en la red local (utilizad ifconfig para averiguarla).
    - *DB_PORT*: puerto de conexión con la base de datos.
    - *DB_DATABASE*: nombre del esquema de la base de datos que se utilizará.
    - *DB_USERNAME*: nombre de usuario para conectar a la base de datos.
    - *DB_PASSWORD*: contraseña de usuario para conectar a la base de datos.
    - *JWT_SECRET*: cadena aleatoria que se utilizará para firmar los token de autenticación JWT. Se recomienda que tenga una longitud mínima de 32 caracteres y que contega letras en mayúsculas y minúsculas y números.
    - *PASSWORD_ALGO*: algoritmo para encriptar las contraseñas de usuario. Los valores admitidos son sha1 y bcrypt. Para compatibilidad total entre clientes web y APIs, utilizad sha1.

5. Construir la imagen de Docker: `docker build -t carlosgmr/api-lumen .`
   Tener en cuenta que hay que estar dentro de la carpeta del proyecto.
6. Construir contenedor Docker con la imagen anterior y ejecutarlo: `docker run -p 8970:80 --detach --memory 1g --name api-lumen carlosgmr/api-lumen`
7. La aplicación se encuentra accesible desde `http://localhost:8970`
