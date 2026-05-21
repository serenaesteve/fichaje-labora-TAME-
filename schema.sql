SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `empleados` (
  `id`       INT AUTO_INCREMENT PRIMARY KEY,
  `nombre`   VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `pin`      CHAR(4) NOT NULL DEFAULT '0000',
  `email`    VARCHAR(150) DEFAULT '',
  `activo`   TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS `dias_de_la_semana` (
  `id`     TINYINT PRIMARY KEY,
  `nombre` VARCHAR(20) NOT NULL
);
INSERT IGNORE INTO `dias_de_la_semana` VALUES
  (1,'Lunes'),(2,'Martes'),(3,'Miércoles'),
  (4,'Jueves'),(5,'Viernes'),(6,'Sábado'),(7,'Domingo');

CREATE TABLE IF NOT EXISTS `horarios` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `empleado`         INT NOT NULL,
  `dia_de_la_semana` TINYINT NOT NULL,
  `hora_de_entrada`  TIME NOT NULL,
  `hora_de_salida`   TIME NOT NULL,
  `fecha_inicio`     DATE DEFAULT NULL,
  `fecha_final`      DATE DEFAULT NULL,
  FOREIGN KEY (`empleado`) REFERENCES `empleados`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`dia_de_la_semana`) REFERENCES `dias_de_la_semana`(`id`)
);

CREATE TABLE IF NOT EXISTS `Festivos` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `fecha`       DATE NOT NULL,
  `nombre`      VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT ''
);

CREATE TABLE IF NOT EXISTS `Asistencia` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `empleado_id`  INT NOT NULL,
  `fecha`        DATE NOT NULL,
  `hora_entrada` TIME DEFAULT NULL,
  `hora_salida`  TIME DEFAULT NULL,
  `editado_por`  INT DEFAULT NULL,
  `nota`         TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empleado_id`) REFERENCES `empleados`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uq_emp_fecha` (`empleado_id`, `fecha`)
);

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(80) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre`        VARCHAR(150) NOT NULL DEFAULT 'Admin',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Login: admin / admin123
INSERT IGNORE INTO `admin_users` (username, password_hash, nombre)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador');

SET FOREIGN_KEY_CHECKS = 1;
