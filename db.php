<?php
// Verificar si la clase Database ya existe antes de declararla
if (!class_exists('Database')) {
    require_once 'config.php'; // Incluir el archivo de configuración

    class Database
    {
        private $conn;

        public function __construct()
        {
            // Establecer la conexión a la base de datos
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->conn->connect_error) {
                $this->conn = null; // Set to null on failure, let caller handle
            }
        }

        /**
         * Método original para ejecutar consultas SQL.
         * No se modifica para mantener compatibilidad.
         *
         * @param string $sql La consulta SQL.
         * @return mysqli_result|bool Resultado de la consulta.
         */
        public function query($sql)
        {
            if (!$this->conn) {
                return false;
            }
            return $this->conn->query($sql);
        }

        /**
         * Nuevo método para ejecutar consultas SQL con parámetros.
         * Usa sentencias preparadas para mayor seguridad.
         *
         * @param string $sql La consulta SQL.
         * @param array $params Parámetros para la consulta (opcional).
         * @return mysqli_result|bool Resultado de la consulta o false on error.
         */
        public function preparedQuery($sql, $params = [])
        {
            if (!$this->conn) {
                return false;
            }
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false; // Return false on preparation error
            }

            // Si hay parámetros, vincularlos
            if (!empty($params)) {
                $types = str_repeat('s', count($params)); // Todos los parámetros son tratados como strings
                $stmt->bind_param($types, ...$params);
            }

            // Ejecutar la consulta
            if (!$stmt->execute()) {
                return false; // Return false on execution error
            }

            // Devolver el resultado (para SELECT)
            return $stmt->get_result();
        }

        // Método para obtener la conexión
        public function getConnection()
        {
            return $this->conn;
        }

        // Método para cerrar la conexión
        public function close()
        {
            if ($this->conn) {
                $this->conn->close();
            }
        }
    }
}
