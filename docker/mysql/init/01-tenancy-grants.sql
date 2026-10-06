-- El usuario de la app crea la BD central y una BD por tienda (prefijo meli_)
GRANT ALL PRIVILEGES ON `meli\_%`.* TO 'meli'@'%';
FLUSH PRIVILEGES;
