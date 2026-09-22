<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * PDO database connection.
 *
 * @package    Kohana/Database
 * @category   Drivers
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 9.08.2023 Закомментирована строка Строка 199: 				//$this->_connection->lastInsertId(),//Бухаров
 после этого вставка данных в БД СКУД стала проходить без ошибок
 19.09.2026 добавлены элементы логирования действия оператора при выполении insert, update, delete.
 
 Для логирования действия в конфигурации подключения к базе данных необходимо добавить параметр log_userconnection.
 'default' => array(
    'type'       => 'PDO',
    'connection' => array(
        'dsn'        => 'odbc:SDUO',
        'username'   => 'SYSDBA',
        'password'   => 'masterkey',
    ),
    'log_userconnection' => TRUE,   // ← включаем аудит для этой БД при наличии таблицы USERCONNECTION.
  ),
Подключения фиксируются в таблице USERCONNECTION
CREATE TABLE USERCONNECTION (
    ID_CONNECT    INTEGER NOT NULL,
	ID_PEP        INTEGER,
    USER_NAME     VARCHAR(50),
    CONNECT_TIME  TIMESTAMP NOT NULL,
    IS_ACTIVE     INTEGER NOT NULL    
);

Триггеры базы данных срабатывают на изменения данных и заносят изменения  в таблицу auditlog
 */
class Kohana_Database_PDO extends Database {

	// PDO uses no quoting for identifiers
	protected $_identifier = '';

	// ID текущего подключения Firebird (CURRENT_CONNECTION)
	protected $_connection_id = NULL;

	// ID оператора, под которым зарегистрировано текущее подключение
	protected $_registered_pep = NULL;

	// Логировать ли подключения в USERCONNECTION (по умолчанию — нет)
	protected $_log_userconnection = FALSE;

	public function __construct($name, array $config)
	{
		parent::__construct($name, $config);

		if (isset($this->_config['identifier']))
		{
			// Allow the identifier to be overloaded per-connection
			$this->_identifier = (string) $this->_config['identifier'];
		}
	}

	public function connect()
	{
		if ($this->_connection)
			return;

		// Extract the connection parameters, adding required variabels
		extract($this->_config['connection'] + array(
			'dsn'        => '',
			'username'   => NULL,
			'password'   => NULL,
			'persistent' => FALSE,
		));

		// Включать ли регистрацию оператора в USERCONNECTION (по умолчанию — нет)
		$this->_log_userconnection = ! empty($this->_config['log_userconnection']);

		// Clear the connection parameters for security
		unset($this->_config['connection']);

		// Force PDO to use exceptions for all errors
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;

		if ( ! empty($persistent))
		{
			// Make the connection persistent
			$options[PDO::ATTR_PERSISTENT] = TRUE;
		}

		try
		{
			// Create a new PDO connection
			$this->_connection = new PDO($dsn, $username, $password, $options);
		}
		catch (PDOException $e)
		{
			// >>> ЛОГИРОВАНИЕ НЕУДАЧНОГО ПОДКЛЮЧЕНИЯ <<<
			Kohana::$log->add(Log::ERROR, 'DB CONNECT FAILED ['.$this->_instance.'] :dsn user=:user error=:error', array(
				':dsn'   => $dsn,
				':user'  => ($username === NULL ? 'NULL' : $username),
				':error' => $e->getMessage(),
			));

			throw new Database_Exception(':error',
				array(':error' => $e->getMessage()),
				$e->getCode());
		}

		// >>> ПОЛУЧАЕМ ID ПОДКЛЮЧЕНИЯ FIREBIRD <<<
		// Нужен только если включена регистрация в USERCONNECTION
		$this->_connection_id = NULL;
		if ($this->_log_userconnection)
		{
			try
			{
				$stmt = $this->_connection->query(
					'SELECT CAST(CURRENT_CONNECTION AS VARCHAR(50)) FROM RDB$DATABASE'
				);
				if ($stmt !== FALSE)
				{
					$this->_connection_id = $stmt->fetchColumn();
					$stmt->closeCursor();
				}
			}
			catch (Exception $e)
			{
				Kohana::$log->add(Log::ERROR, 'DB CURRENT_CONNECTION query failed: :err', array(
					':err' => $e->getMessage(),
				));
			}
		}

		// >>> ЛОГИРОВАНИЕ УСПЕШНОГО ПОДКЛЮЧЕНИЯ <<<
		Kohana::$log->add(Log::INFO, 'DB CONNECT ['.$this->_instance.'] :dsn user=:user persistent=:persistent conn_id=:conn_id', array(
			':dsn'        => $dsn,
			':user'       => ($username === NULL ? 'NULL' : $username),
			':persistent' => ($persistent ? 'yes' : 'no'),
			':conn_id'    => ($this->_connection_id === NULL ? 'NULL' : $this->_connection_id),
		));

		if ( ! empty($this->_config['charset']))
		{
			// Set the character set
			$this->set_charset($this->_config['charset']);
		}
	}

	/**
	 * Регистрирует текущее подключение в USERCONNECTION.
	 * Вызывается лениво — только перед первым DML-запросом (INSERT/UPDATE/DELETE).
	 * Повторно для того же подключения в БД не ходит.
	 */
	protected function _register_operator()
	{
		if ( ! $this->_log_userconnection)
		{
			return;
		}

		if ($this->_registered_pep !== NULL)
		{
			return;
		}

		if ($this->_connection_id === NULL OR $this->_connection === NULL)
		{
			return;
		}

		// >>> ПОЛУЧАЕМ id_pep ТЕКУЩЕГО ОПЕРАТОРА <<<
		$id_pep    = NULL;
		$user_name = NULL;

		try
		{
			if (class_exists('Auth') AND Auth::instance()->logged_in())
			{
				$user = Auth::instance()->get_user();
				if ($user)
				{
					$id_pep    = $user->id_pep;
					$user_name = $user->username;
				}
			}
		}
		catch (Exception $e)
		{
			Kohana::$log->add(Log::ERROR, 'DB get operator failed: :err', array(
				':err' => $e->getMessage(),
			));
		}

		if ($id_pep === NULL)
		{
			return;
		}

		try
		{
			// Убираем возможный «хвост» от упавшего скрипта
			$stmt = $this->_connection->prepare(
				'DELETE FROM USERCONNECTION WHERE ID_CONNECT = :conn_id'
			);
			$stmt->bindValue(':conn_id', $this->_connection_id, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();

			// Вставляем заново
			$stmt = $this->_connection->prepare(
				'INSERT INTO USERCONNECTION
					(ID_CONNECT, ID_PEP, USER_NAME, CONNECT_TIME, IS_ACTIVE)
				 VALUES (:conn_id, :id_pep, :user_name, CURRENT_TIMESTAMP, 1)'
			);
			$stmt->bindValue(':conn_id',   $this->_connection_id, PDO::PARAM_INT);
			$stmt->bindValue(':id_pep',    $id_pep,               PDO::PARAM_INT);
			$stmt->bindValue(':user_name', $user_name,            PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();

			$this->_registered_pep = $id_pep;

			Kohana::$log->add(Log::INFO, 'DB USERCONNECTION register conn_id=:conn_id id_pep=:id_pep user=:user', array(
				':conn_id' => $this->_connection_id,
				':id_pep'  => $id_pep,
				':user'    => ($user_name === NULL ? 'NULL' : $user_name),
			));
		}
		catch (Exception $e)
		{
			Kohana::$log->add(Log::ERROR, 'DB USERCONNECTION register failed: :err', array(
				':err' => $e->getMessage(),
			));
		}
	}

	/**
	 * Снимает регистрацию текущего подключения в USERCONNECTION.
	 */
	protected function _unregister_operator()
	{
		if ( ! $this->_log_userconnection)
		{
			return;
		}

		if ($this->_connection_id === NULL OR $this->_connection === NULL)
		{
			return;
		}

		try
		{
			$stmt = $this->_connection->prepare(
				'UPDATE USERCONNECTION SET IS_ACTIVE = 0 WHERE ID_CONNECT = ?'
			);
			$stmt->execute(array($this->_connection_id));
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			// Не используем Kohana::$log: объект может разрушаться
			error_log('DB USERCONNECTION unregister failed: '.$e->getMessage());
		}
	}

	/**
	 * Create or redefine a SQL aggregate function.
	 *
	 * [!!] Works only with SQLite
	 *
	 * @link http://php.net/manual/function.pdo-sqlitecreateaggregate
	 *
	 * @param   string      $name       Name of the SQL function to be created or redefined
	 * @param   callback    $step       Called for each row of a result set
	 * @param   callback    $final      Called after all rows of a result set have been processed
	 * @param   integer     $arguments  Number of arguments that the SQL function takes
	 *
	 * @return  boolean
	 */
	public function create_aggregate($name, $step, $final, $arguments = -1)
	{
		$this->_connection or $this->connect();

		return $this->_connection->sqliteCreateAggregate(
			$name, $step, $final, $arguments
		);
	}

	/**
	 * Create or redefine a SQL function.
	 *
	 * [!!] Works only with SQLite
	 *
	 * @link http://php.net/manual/function.pdo-sqlitecreatefunction
	 *
	 * @param   string      $name       Name of the SQL function to be created or redefined
	 * @param   callback    $callback   Callback which implements the SQL function
	 * @param   integer     $arguments  Number of arguments that the SQL function takes
	 *
	 * @return  boolean
	 */
	public function create_function($name, $callback, $arguments = -1)
	{
		$this->_connection or $this->connect();

		return $this->_connection->sqliteCreateFunction(
			$name, $callback, $arguments
		);
	}

	public function disconnect()
	{
		// >>> СНИМАЕМ РЕГИСТРАЦИЮ ОПЕРАТОРА <<<
		$this->_unregister_operator();

		// Destroy the PDO object
		$this->_connection = NULL;
		$this->_connection_id = NULL;
		$this->_registered_pep = NULL;

		return parent::disconnect();
	}

	/**
	 * Гарантированное снятие регистрации при уничтожении объекта.
	 * При persistent-соединениях реального закрытия не происходит,
	 * поэтому запись может остаться IS_ACTIVE = 1 — чистите cron'ом.
	 */
	public function __destruct()
	{
		$this->_unregister_operator();
	}

	public function set_charset($charset)
	{
		// Make sure the database is connected
		$this->_connection OR $this->connect();

		// This SQL-92 syntax is not supported by all drivers
		$this->_connection->exec('SET NAMES '.$this->quote($charset));
	}

	public function query($type, $sql, $as_object = FALSE, array $params = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		// >>> РЕГИСТРИРУЕМ ОПЕРАТОРА ТОЛЬКО ПЕРЕД DML И ТОЛЬКО ЕСЛИ ВКЛЮЧЕНО <<<
		if ($type !== Database::SELECT AND $this->_log_userconnection)
		{
			$this->_register_operator();
		}

		if (Kohana::$profiling)
		{
			// Benchmark this query for the current instance
			$benchmark = Profiler::start("Database ({$this->_instance})", $sql);
		}

		try
		{
			$result = $this->_connection->query($sql);
		}
		catch (Exception $e)
		{
			if (isset($benchmark))
			{
				// This benchmark is worthless
				Profiler::delete($benchmark);
			}

			// Convert the exception in a database exception
			throw new Database_Exception(':error [ :query ]',
				array(
					':error' => $e->getMessage(),
					':query' => $sql
				),
				$e->getCode());
		}

		if (isset($benchmark))
		{
			Profiler::stop($benchmark);
		}

		// Set the last query
		$this->last_query = $sql;

		if ($type === Database::SELECT)
		{
			// Convert the result into an array, as PDOStatement::rowCount is not reliable
			if ($as_object === FALSE)
			{
				$result->setFetchMode(PDO::FETCH_ASSOC);
			}
			elseif (is_string($as_object))
			{
				$result->setFetchMode(PDO::FETCH_CLASS, $as_object, $params);
			}
			else
			{
				$result->setFetchMode(PDO::FETCH_CLASS, 'stdClass');
			}

			$result = $result->fetchAll();

			// Return an iterator of results
			return new Database_Result_Cached($result, $sql, $as_object, $params);
		}
		elseif ($type === Database::INSERT)
		{
			// Return a list of insert id and rows created
			return array(
				//$this->_connection->lastInsertId(),//Бухаров
				$result->rowCount(),
			);
		}
		else
		{
			// Return the number of rows affected
			return $result->rowCount();
		}
	}

	public function begin($mode = NULL)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->beginTransaction();
	}

	public function commit()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->commit();
	}

	public function rollback()
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->rollBack();
	}

	public function list_tables($like = NULL)
	{
		throw new Kohana_Exception('Database method :method is not supported by :class',
			array(':method' => __FUNCTION__, ':class' => __CLASS__));
	}

	public function list_columns($table, $like = NULL, $add_prefix = TRUE)
	{
		throw new Kohana_Exception('Database method :method is not supported by :class',
			array(':method' => __FUNCTION__, ':class' => __CLASS__));
	}

	public function escape($value)
	{
		// Make sure the database is connected
		$this->_connection or $this->connect();

		return $this->_connection->quote($value);
	}

} // End Database_PDO