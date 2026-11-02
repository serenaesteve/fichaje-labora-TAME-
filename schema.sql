PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS empleados (
  id       INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre   TEXT NOT NULL,
  apellido TEXT NOT NULL,
  pin      TEXT NOT NULL DEFAULT '0000',
  email    TEXT DEFAULT '',
  activo   INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS dias_de_la_semana (
  id     INTEGER PRIMARY KEY,
  nombre TEXT NOT NULL
);
INSERT OR IGNORE INTO dias_de_la_semana VALUES
  (1,'Lunes'),(2,'Martes'),(3,'Miércoles'),
  (4,'Jueves'),(5,'Viernes'),(6,'Sábado'),(7,'Domingo');

CREATE TABLE IF NOT EXISTS horarios (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  empleado         INTEGER NOT NULL,
  dia_de_la_semana INTEGER NOT NULL,
  hora_de_entrada  TEXT NOT NULL,
  hora_de_salida   TEXT NOT NULL,
  fecha_inicio     TEXT DEFAULT NULL,
  fecha_final      TEXT DEFAULT NULL,
  FOREIGN KEY (empleado) REFERENCES empleados(id) ON DELETE CASCADE,
  FOREIGN KEY (dia_de_la_semana) REFERENCES dias_de_la_semana(id)
);

CREATE TABLE IF NOT EXISTS Festivos (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  fecha       TEXT NOT NULL,
  nombre      TEXT NOT NULL,
  descripcion TEXT DEFAULT ''
);

CREATE TABLE IF NOT EXISTS Asistencia (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  empleado_id  INTEGER NOT NULL,
  fecha        TEXT NOT NULL,
  hora_entrada TEXT DEFAULT NULL,
  hora_salida  TEXT DEFAULT NULL,
  editado_por  INTEGER DEFAULT NULL,
  nota         TEXT DEFAULT NULL,
  created_at   TEXT DEFAULT (datetime('now','localtime')),
  updated_at   TEXT DEFAULT (datetime('now','localtime')),
  FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE,
  UNIQUE (empleado_id, fecha)
);

CREATE TABLE IF NOT EXISTS admin_users (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  username      TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  nombre        TEXT NOT NULL DEFAULT 'Admin',
  created_at    TEXT DEFAULT (datetime('now','localtime'))
);

-- Login: admin / admin123
INSERT OR IGNORE INTO admin_users (username, password_hash, nombre)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador');
