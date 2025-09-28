<?php

require_once __DIR__ . '/../vendor/autoload.php';

class MigrationRunner
{
    private $pdo;
    private $migrations = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createMigrationsTable();
        $this->loadMigrationFiles();
    }

    private function createMigrationsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255),
                batch INT,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->pdo->exec($sql);
    }

    private function loadMigrationFiles()
    {
        $files = glob(__DIR__ . '/migrations/*.php');
        sort($files);
        
        foreach ($files as $file) {
            require_once $file;
            $className = 'Database\\Migrations\\' . basename($file, '.php');
            $this->migrations[] = new $className();
        }
    }

    public function migrate()
    {
        $batch = $this->getNextBatchNumber();
        
        try {
            $this->pdo->beginTransaction();

            foreach ($this->migrations as $migration) {
                $className = get_class($migration);
                if (!$this->hasRun($className)) {
                    $sql = $migration->up();
                    $this->pdo->exec($sql);
                    
                    // Record migration
                    $stmt = $this->pdo->prepare("
                        INSERT INTO migrations (migration, batch) 
                        VALUES (:migration, :batch)
                    ");
                    $stmt->execute([
                        ':migration' => $className,
                        ':batch' => $batch
                    ]);
                    
                    echo "Migrated: " . $className . PHP_EOL;
                }
            }

            $this->pdo->commit();
            echo "Database migration completed successfully!" . PHP_EOL;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "Migration failed: " . $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }

    public function rollback()
    {
        $lastBatch = $this->getLastBatchNumber();
        if (!$lastBatch) {
            echo "Nothing to rollback." . PHP_EOL;
            return;
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                SELECT migration FROM migrations 
                WHERE batch = :batch 
                ORDER BY id DESC
            ");
            $stmt->execute([':batch' => $lastBatch]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $migration = $row['migration'];
                $instance = new $migration();
                
                $sql = $instance->down();
                $this->pdo->exec($sql);

                $this->pdo->prepare("
                    DELETE FROM migrations 
                    WHERE migration = :migration
                ")->execute([':migration' => $migration]);

                echo "Rolled back: " . $migration . PHP_EOL;
            }

            $this->pdo->commit();
            echo "Rollback completed successfully!" . PHP_EOL;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            echo "Rollback failed: " . $e->getMessage() . PHP_EOL;
            exit(1);
        }
    }

    private function hasRun($migration)
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM migrations 
            WHERE migration = :migration
        ");
        $stmt->execute([':migration' => $migration]);
        return (bool) $stmt->fetchColumn();
    }

    private function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    private function getLastBatchNumber()
    {
        return (int) $this->pdo->query("
            SELECT MAX(batch) FROM migrations
        ")->fetchColumn();
    }
}