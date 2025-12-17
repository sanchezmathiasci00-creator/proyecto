-- Datos de prueba para la tabla `especialidades`
INSERT INTO `especialidades` (`id_especialidades`, `nm_especialidades`) VALUES
(1, 'Gestión Administrativa'),
(2, 'Administración de Empresas'),
(3, 'Computación e Informática'),
(4, 'Desarrollo de Sistemas de Información'),
(5, 'Contabilidad');

-- Datos de prueba para la tabla `modulos`
INSERT INTO `modulos` (`id_modulos`, `id_especialidad`, `nm_modulos`, `horas_requeridas`) VALUES
(1, 2, 'Gestión Administrativa', 286),
(2, 2, 'Gestión de la Comercialización', 296),
(3, 2, 'Gestión de Recursos Financieros y Proyectos', 296),
(4, 1, 'Procedimientos Administrativos', 128),
(5, 1, 'Presupuestos y Servicios Especializados', 128),
(6, 1, 'Administración de Recursos y Supervisión del Plan Operativo', 128),
(7, 3, 'Soporte Técnico', 286),
(8, 3, 'Base de Datos', 296),
(9, 3, 'Multimedia', 296),
(10, 4, 'Gestión de Soporte y Tecnologías de Desarrollo de Software', 128),
(11, 4, 'Gestión de Base de Datos y Análisis de Sistemas de Información', 128),
(12, 4, 'Gestión de Calidad de Sistemas de Información', 128),
(13, 5, 'Desarrollo de Procesos Contables', 128),
(14, 5, 'Formulación y Supervisión de Operaciones Contables', 128),
(15, 5, 'Análisis Financiero', 128);

-- Datos de prueba para la tabla `turnos`
INSERT INTO `turnos` (`id_turnos`, `nm_turnos`) VALUES
(1, 'Diurno'),
(2, 'Nocturno');

-- Datos de prueba para la tabla `usuarios`
INSERT INTO `usuarios` (`id_usuario`, `dni_practicante`, `nombre`, `cargo`, `usuario`, `clave`, `rol`) VALUES
(1, NULL, 'Elmer Negrón', 'Coordinador', 'elmern', '$2y$10$oj5W0FbxH/l2FNoDACrXlO6h3U0JZ7hgXJEArrQqvl5zYKCQ70nRS', 'COORDINADOR'),
(2, NULL, 'Yoselin Benites', 'Asistente', 'yoselinb', '$2y$10$5LB2GyNotcvLCFQ6ZFmnxu2UXo3eCOpLn3LqvF1PCmswpP5N4W9DG', 'ASISTENTE'),
(3, NULL, 'Harvey Sánchez', 'SysAdmin', 'harveys', '$2y$10$s69ubsgHhv88lINjicfsteh0rZI33QpIOLWPeMvGhJarq6jMBf12G', 'ADMIN'),
(12, NULL, 'Admin Test', 'Admin', 'admin', '$2y$10$3H30w4PowPni6e4Q1rWPaewjesG6abF3quaHPtiVCdnyklZnHjjPu', 'ADMIN');

-- Datos de prueba para la tabla `practicantes`
INSERT INTO `practicantes` (`dni`, `nombres`, `apellidos`, `semestre`, `id_especialidades`, `id_modulos`, `id_turnos`, `id_usuario`, `foto`) VALUES
(72894561, 'Jose Antonio', 'Sanchez Gutierrez', 'VI', 2, 1, 2, NULL, 'uploads/fotos_perfil/72894561_1764721737.png'),
(74660226, 'Luis Enrique', 'Aguilar Achas', 'VI', 3, 9, 2, NULL, 'uploads/fotos_perfil/74660226_1764722785.png'),
(77777777, 'Alumno', 'Test', 'I', 4, 10, 1, NULL, 'default.png');

-- Actualizar usuarios con referencia a practicantes
UPDATE `usuarios` SET `dni_practicante` = 72894561 WHERE `id_usuario` = 4;
UPDATE `usuarios` SET `dni_practicante` = 74660226 WHERE `id_usuario` = 6;
UPDATE `usuarios` SET `dni_practicante` = 77777777 WHERE `id_usuario` = 11;

-- Insertar usuarios para practicantes (si no existen)
INSERT INTO `usuarios` (`id_usuario`, `dni_practicante`, `nombre`, `cargo`, `usuario`, `clave`, `rol`) VALUES
(4, 72894561, 'José Sanchez', 'Practicante', 'joses', '$2y$10$LXlHN6y55FpTmz4wgRozu.QWgPqdcxHAB/zcAjJO3wKIV8z8oQoeC', 'PRACTICANTE'),
(6, 74660226, 'Luis Aguilar', 'Practicante', 'luisa', '$2y$10$p3M70qKubyIuPl4L/96UmeKK3kE7m/hlZGN83WOFou/umRhIg5A46', 'PRACTICANTE'),
(11, 77777777, 'Alumno', 'Practicante', 'alumnotest', '$2y$10$.uop4h1a7VM0QzkMkomHqODnVJNSjtFxWrZ6E4RTgTq.gndbAKHyy', 'PRACTICANTE');

-- Actualizar practicantes con referencia a usuarios
UPDATE `practicantes` SET `id_usuario` = 4 WHERE `dni` = 72894561;
UPDATE `practicantes` SET `id_usuario` = 6 WHERE `dni` = 74660226;
UPDATE `practicantes` SET `id_usuario` = 11 WHERE `dni` = 77777777;

-- Datos de prueba para la tabla `asistencia`
INSERT INTO `asistencia` (`id_asistencia`, `dni_practicante`, `id_modulo`, `fecha`, `hora_ingreso`, `hora_salida`, `tareas_realizadas`, `firma`, `id_usuario`, `estado`) VALUES
(8, 72894561, 1, '2025-10-29', '10:34:00', '18:34:27', NULL, NULL, NULL, 'COMPLETADO'),
(9, 72894561, 1, '2025-10-30', '10:35:08', '16:35:15', NULL, NULL, NULL, 'COMPLETADO'),
(10, 72894561, 1, '2025-10-31', '16:30:50', '22:20:04', NULL, NULL, NULL, 'COMPLETADO'),
(11, 72894561, 1, '2025-10-01', '13:23:53', '20:24:05', NULL, NULL, NULL, 'COMPLETADO'),
(14, 74660226, 9, '2025-12-02', '19:57:18', '20:31:47', 'Instalación de sistemas operativos', NULL, NULL, 'COMPLETADO'),
(15, 72894561, 1, '2025-12-02', '20:26:28', '20:28:34', NULL, NULL, NULL, 'COMPLETADO'),
(17, 72894561, 1, '2025-12-17', '05:32:32', '00:00:00', NULL, NULL, NULL, 'EN_CURSO'),
(18, 77777777, 10, '2025-10-01', '08:00:00', '14:00:00', 'Revisión de documentación técnica y configuración inicial de entorno de desarrollo', NULL, NULL, 'COMPLETADO'),
(19, 77777777, 10, '2025-10-02', '08:00:00', '14:00:00', 'Análisis de requisitos del sistema de gestión de prácticas pre profesionales', NULL, NULL, 'COMPLETADO'),
(20, 77777777, 10, '2025-10-03', '08:00:00', '14:00:00', 'Diseño de base de datos y modelado de entidades principales', NULL, NULL, 'COMPLETADO'),
(21, 77777777, 10, '2025-10-06', '08:00:00', '14:00:00', 'Implementación de módulo de autenticación de usuarios', NULL, NULL, 'COMPLETADO'),
(22, 77777777, 10, '2025-10-07', '08:00:00', '14:00:00', 'Desarrollo de CRUD para gestión de practicantes', NULL, NULL, 'COMPLETADO'),
(23, 77777777, 10, '2025-10-08', '08:00:00', '14:00:00', 'Implementación de registro de asistencia con validaciones', NULL, NULL, 'COMPLETADO'),
(24, 77777777, 10, '2025-10-09', '08:00:00', '14:00:00', 'Desarrollo de reportes de horas acumuladas por practicante', NULL, NULL, 'COMPLETADO'),
(25, 77777777, 10, '2025-10-10', '08:00:00', '14:00:00', 'Integración de firma digital en registro de asistencia', NULL, NULL, 'COMPLETADO'),
(26, 77777777, 10, '2025-10-13', '08:00:00', '14:00:00', 'Testing de funcionalidades principales y corrección de bugs', NULL, NULL, 'COMPLETADO'),
(27, 77777777, 10, '2025-10-14', '08:00:00', '14:00:00', 'Optimización de consultas a base de datos y mejora de rendimiento', NULL, NULL, 'COMPLETADO'),
(28, 77777777, 10, '2025-10-15', '08:00:00', '14:00:00', 'Implementación de módulo de notificaciones por email', NULL, NULL, 'COMPLETADO'),
(29, 77777777, 10, '2025-10-16', '08:00:00', '14:00:00', 'Desarrollo de panel de control para coordinadores', NULL, NULL, 'COMPLETADO'),
(30, 77777777, 10, '2025-10-17', '08:00:00', '14:00:00', 'Creación de gráficos estadísticos para reportes', NULL, NULL, 'COMPLETADO'),
(31, 77777777, 10, '2025-10-20', '08:00:00', '14:00:00', 'Configuración de permisos y roles de usuario', NULL, NULL, 'COMPLETADO'),
(32, 77777777, 10, '2025-10-21', '08:00:00', '14:00:00', 'Documentación técnica del sistema', NULL, NULL, 'COMPLETADO'),
(33, 77777777, 10, '2025-10-22', '08:00:00', '14:00:00', 'Pruebas de integración entre módulos', NULL, NULL, 'COMPLETADO'),
(34, 77777777, 10, '2025-10-23', '08:00:00', '14:00:00', 'Implementación de backup automático de base de datos', NULL, NULL, 'COMPLETADO'),
(35, 77777777, 10, '2025-10-24', '08:00:00', '14:00:00', 'Mejora de interfaz de usuario y experiencia UX', NULL, NULL, 'COMPLETADO'),
(36, 77777777, 10, '2025-10-27', '08:00:00', '18:00:00', 'Desarrollo de API REST para consumo externo', NULL, NULL, 'COMPLETADO'),
(37, 77777777, 10, '2025-10-28', '08:00:00', '18:00:00', 'Configuración de servidor de producción', NULL, NULL, 'COMPLETADO');

-- Establecer el AUTO_INCREMENT para las tablas
ALTER TABLE `asistencia` AUTO_INCREMENT = 61;
ALTER TABLE `especialidades` AUTO_INCREMENT = 6;
ALTER TABLE `modulos` AUTO_INCREMENT = 16;
ALTER TABLE `turnos` AUTO_INCREMENT = 3;
ALTER TABLE `usuarios` AUTO_INCREMENT = 13;