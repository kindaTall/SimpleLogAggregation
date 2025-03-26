<?php

namespace App\Models;

use PDO;

class LogModel
{
    public $id;
    public $host;
    public $host_process;
    public $log_level;
    public $log_message;
    public $timestamp;
    public $created_at;

    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(): int
    {
        $sql = "INSERT INTO logs (host, host_process, log_level, log_message, timestamp) VALUES (:host, :host_process, :log_level, :log_message, :timestamp)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':host' => $this->host,
            ':host_process' => $this->host_process,
            ':log_level' => $this->log_level,
            ':log_message' => $this->log_message,
            ':timestamp' => $this->timestamp,
        ]);

        return $this->db->lastInsertId();
    }

    public static function getLogs(PDO $db, string $host = null, string $hostProcess = null, string $logLevel = null, string $timestampFrom = null, string $timestampTo = null): array
    {
        $sql = "SELECT * FROM logs WHERE 1=1";

        if ($host) {
            $sql .= " AND host = :host";
        }
        if ($hostProcess) {
            $sql .= " AND host_process = :host_process";
        }
        if ($logLevel) {
            $sql .= " AND log_level = :log_level";
        }
        if ($timestampFrom) {
            $sql .= " AND timestamp >= :timestamp_from";
        }
        if ($timestampTo) {
            $sql .= " AND timestamp <= :timestamp_to";
        }

        $stmt = $db->prepare($sql);

        $params = [];
        if ($host) {
            $params[':host'] = $host;
        }
        if ($hostProcess) {
            $params[':host_process'] = $hostProcess;
        }
        if ($logLevel) {
            $params[':log_level'] = $logLevel;
        }
        if ($timestampFrom) {
            $params[':timestamp_from'] = $timestampFrom;
        }
        if ($timestampTo) {
            $params[':timestamp_to'] = $timestampTo;
        }

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
