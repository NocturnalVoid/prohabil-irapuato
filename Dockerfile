# Usar la imagen oficial de PHP 8.2 con Apache integrado
FROM php:8.2-apache

# Actualizar el sistema e instalar las extensiones de base de datos
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Opcional: Habilitar mod_rewrite de Apache (muy útil para URLs limpias en MVC)
RUN a2enmod rewrite